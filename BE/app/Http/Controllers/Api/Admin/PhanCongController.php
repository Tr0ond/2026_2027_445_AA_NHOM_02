<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\GiangVien;
use App\Models\LichHoc;
use App\Models\LopHoc;
use App\Models\PhanCongGiangDay;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** US21 - Phân công giảng dạy. */
class PhanCongController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $danhSach = LopHoc::with(['monHoc', 'giangVienPhuTrach.taiKhoan'])
            ->withCount(['dangKy as so_sinh_vien' => fn ($q) => $q->where('trang_thai', 'da_duyet')])
            ->orderByDesc('id')
            ->get()
            ->map(fn ($lop) => [
                'id' => $lop->id,
                'ma_lop_hoc' => $lop->ma_lop_hoc,
                'ten_lop' => $lop->ten_lop,
                'mon_hoc' => $lop->monHoc?->ten_mon,
                'hoc_ky' => $lop->hoc_ky,
                'nam_hoc' => $lop->nam_hoc,
                'trang_thai' => $lop->trang_thai,
                'so_sinh_vien' => $lop->so_sinh_vien,
                'giang_vien' => $lop->giangVienPhuTrach->map(fn ($gv) => [
                    'id' => $gv->id,
                    'ho_ten' => $gv->taiKhoan?->ho_ten,
                    'vai_tro' => $gv->pivot->vai_tro_phu_trach,
                ]),
            ]);

        return response()->json(['danh_sach' => $danhSach]);
    }

    public function danhSachGiangVien(): JsonResponse
    {
        return response()->json([
            'danh_sach' => GiangVien::with('taiKhoan:id,ho_ten')
                ->get()
                ->map(fn ($gv) => [
                    'id' => $gv->id,
                    'ma_giang_vien' => $gv->ma_giang_vien,
                    'ho_ten' => $gv->taiKhoan?->ho_ten,
                ]),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ma_giang_vien' => ['required', 'integer', 'exists:giang_vien,id'],
            'ma_lop_hoc' => [
                'required',
                'integer',
                'exists:lop_hoc,id',
                Rule::unique('phan_cong_giang_day', 'ma_lop_hoc'),
            ],
            'vai_tro_phu_trach' => ['nullable', 'string', 'max:50'],
        ], [
            'ma_lop_hoc.unique' => 'Lớp học này đã được phân công cho một giảng viên.',
        ]);

        try {
            DB::transaction(function () use ($data) {
                $giangVien = GiangVien::query()->lockForUpdate()->findOrFail($data['ma_giang_vien']);
                $lopHoc = LopHoc::with('lichHoc')->findOrFail($data['ma_lop_hoc']);

                if (PhanCongGiangDay::where('ma_lop_hoc', $lopHoc->id)->exists()) {
                    throw ValidationException::withMessages([
                        'ma_lop_hoc' => 'Lớp học này đã được phân công cho một giảng viên.',
                    ]);
                }

                $xungDot = $this->timLichXungDot($giangVien, $lopHoc);
                if ($xungDot) {
                    [$lichMoi, $lichDaCo] = $xungDot;
                    throw ValidationException::withMessages([
                        'ma_lop_hoc' => sprintf(
                            'Không thể phân công: lịch lớp %s ngày %s (%s–%s) trùng với lớp %s (%s–%s) của giảng viên.',
                            $lopHoc->ma_lop_hoc,
                            $lichMoi->ngay_hoc->format('d/m/Y'),
                            $lichMoi->gio_bat_dau->format('H:i'),
                            $lichMoi->gio_ket_thuc->format('H:i'),
                            $lichDaCo->lopHoc?->ma_lop_hoc,
                            $lichDaCo->gio_bat_dau->format('H:i'),
                            $lichDaCo->gio_ket_thuc->format('H:i'),
                        ),
                    ]);
                }

                PhanCongGiangDay::create($data + [
                    'vai_tro_phu_trach' => $data['vai_tro_phu_trach'] ?? 'giang_vien_chinh',
                ]);
            });
        } catch (QueryException $exception) {
            // Ràng buộc unique trong CSDL xử lý cả trường hợp hai yêu cầu đến đồng thời.
            if (PhanCongGiangDay::where('ma_lop_hoc', $data['ma_lop_hoc'])->exists()) {
                throw ValidationException::withMessages([
                    'ma_lop_hoc' => 'Lớp học này đã được phân công cho một giảng viên.',
                ]);
            }

            throw $exception;
        }

        return response()->json(['message' => 'Đã phân công giảng dạy.'], 201);
    }

    public function destroy(Request $request, ?PhanCongGiangDay $phanCong = null): JsonResponse
    {
        // Hỗ trợ hủy theo cặp (ma_giang_vien, ma_lop_hoc) qua query string
        if ($request->filled('ma_giang_vien') && $request->filled('ma_lop_hoc')) {
            $soBanGhi = PhanCongGiangDay::where('ma_giang_vien', $request->ma_giang_vien)
                ->where('ma_lop_hoc', $request->ma_lop_hoc)
                ->delete();

            if (! $soBanGhi) {
                return response()->json(['message' => 'Không tìm thấy phân công cần hủy.'], 404);
            }

            return response()->json(['message' => 'Đã hủy phân công.']);
        }

        $phanCong->delete();

        return response()->json(['message' => 'Đã hủy phân công.']);
    }

    private function timLichXungDot(GiangVien $giangVien, LopHoc $lopMoi): ?array
    {
        $lichMoi = $lopMoi->lichHoc
            ->where('trang_thai', '!=', 'da_huy')
            ->reject(fn (LichHoc $lich) => $lich->daQuaGioHoc());

        if ($lichMoi->isEmpty()) {
            return null;
        }

        $lichDaCo = LichHoc::with('lopHoc:id,ma_lop_hoc,ten_lop')
            ->where('trang_thai', '!=', 'da_huy')
            ->whereHas('lopHoc.phanCong', fn ($query) => $query->where('ma_giang_vien', $giangVien->id))
            ->get()
            ->reject(fn (LichHoc $lich) => $lich->daQuaGioHoc());

        foreach ($lichMoi as $buoiMoi) {
            foreach ($lichDaCo as $buoiDaCo) {
                $cungNgay = $buoiMoi->ngay_hoc->isSameDay($buoiDaCo->ngay_hoc);
                $giaoNhau = $buoiMoi->gio_bat_dau->format('H:i:s') < $buoiDaCo->gio_ket_thuc->format('H:i:s')
                    && $buoiMoi->gio_ket_thuc->format('H:i:s') > $buoiDaCo->gio_bat_dau->format('H:i:s');

                if ($cungNgay && $giaoNhau) {
                    return [$buoiMoi, $buoiDaCo];
                }
            }
        }

        return null;
    }
}
