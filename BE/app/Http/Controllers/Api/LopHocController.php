<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DangKyLopHoc;
use App\Models\LichHoc;
use App\Models\LopHoc;
use App\Models\SinhVien;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LopHocController extends Controller
{
    /** US04 - Danh sách lớp đang mở đăng ký (kèm trạng thái đăng ký của sinh viên hiện tại). */
    public function danhSachMo(Request $request): JsonResponse
    {
        $sinhVien = $request->user()->sinhVien;

        $daDangKy = $sinhVien
            ? DangKyLopHoc::where('ma_sinh_vien', $sinhVien->id)->pluck('ma_lop_hoc')->all()
            : [];

        $lops = LopHoc::with('monHoc')
            ->withCount(['dangKy as so_luong_dang_ky' => fn ($q) => $q->where('trang_thai', '!=', 'huy')])
            ->with(['lichHoc' => fn ($q) => $q->select('id', 'ma_lop_hoc', 'ngay_hoc', 'gio_bat_dau', 'gio_ket_thuc')])
            ->whereIn('trang_thai', ['mo_dang_ky', 'dang_hoc'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function (LopHoc $lop) use ($daDangKy) {
                return [
                    'id' => $lop->id,
                    'ma_lop_hoc' => $lop->ma_lop_hoc,
                    'ten_lop' => $lop->ten_lop,
                    'mon_hoc' => $lop->monHoc?->ten_mon,
                    'ma_mon_hoc' => $lop->monHoc?->id,
                    'so_tin_chi' => $lop->monHoc?->so_tin_chi,
                    'hoc_ky' => $lop->hoc_ky,
                    'nam_hoc' => $lop->nam_hoc,
                    'trang_thai' => $lop->trang_thai,
                    'so_luong_toi_da' => $lop->so_luong_toi_da,
                    'so_luong_dang_ky' => $lop->so_luong_dang_ky,
                    'da_dang_ky' => in_array($lop->id, $daDangKy),
                    // Chỉ được đăng ký trong tuần đầu tiên của kỳ (tuần học thử)
                    'con_dang_ky' => $this->conTuanDauTien($lop),
                    // Chỉ được hủy trong tuần đầu tiên của kỳ (áp dụng cho lớp đã đăng ký)
                    'con_huy' => $this->conTuanDauTien($lop),
                    // lịch học gọn để FE tự nhận diện trùng lịch trước khi đăng ký
                    'lich_hoc' => $lop->lichHoc->map(fn ($lh) => [
                        'ngay_hoc' => $lh->ngay_hoc?->format('Y-m-d'),
                        'gio_bat_dau' => $lh->gio_bat_dau?->format('H:i'),
                        'gio_ket_thuc' => $lh->gio_ket_thuc?->format('H:i'),
                    ]),
                ];
            });

        return response()->json(['danh_sach' => $lops]);
    }

    /**
     * Tuần đầu tiên của kỳ học = 7 ngày kể từ buổi học đầu tiên của lớp (tuần học thử).
     * Qua tuần này thì không được đăng ký mới hay hủy lớp. Lớp chưa có lịch → vẫn coi là đăng ký được.
     */
    private function conTuanDauTien(LopHoc $lopHoc): bool
    {
        $buoiDauTien = $lopHoc->lichHoc()->min('ngay_hoc');

        if (! $buoiDauTien) {
            return true;
        }

        return now()->startOfDay()->lte(\Illuminate\Support\Carbon::parse($buoiDauTien)->addDays(6));
    }

    /** US04 - Đăng ký lớp học (chặn trùng môn, trùng lịch, hết chỗ, quá tuần học thử). */
    public function dangKy(Request $request, LopHoc $lopHoc): JsonResponse
    {
        /** @var SinhVien|null $sinhVien */
        $sinhVien = $request->user()->sinhVien;
        if (! $sinhVien) {
            return response()->json(['message' => 'Tài khoản không phải sinh viên.'], 403);
        }

        if ($lopHoc->trang_thai === 'da_ket_thuc') {
            return response()->json(['message' => 'Lớp học đã kết thúc, không thể đăng ký.'], 422);
        }

        // Chỉ được đăng ký môn mới trong tuần đầu tiên của kỳ (học thử 1 tuần)
        if (! $this->conTuanDauTien($lopHoc)) {
            return response()->json([
                'message' => 'Đã quá tuần học đầu tiên của kỳ — không thể đăng ký môn mới.',
            ], 422);
        }

        if (DangKyLopHoc::where('ma_sinh_vien', $sinhVien->id)
            ->where('ma_lop_hoc', $lopHoc->id)
            ->where('trang_thai', '!=', 'huy')
            ->exists()) {
            return response()->json(['message' => 'Bạn đã đăng ký lớp học này.'], 422);
        }

        if ($lopHoc->soLuongDaDangKy() >= $lopHoc->so_luong_toi_da) {
            return response()->json(['message' => 'Lớp học đã đủ số lượng.'], 422);
        }

        // Điều kiện 1: không đăng ký 2 lớp cùng một môn học
        $trungMon = LopHoc::where('ma_mon_hoc', $lopHoc->ma_mon_hoc)
            ->whereHas('dangKy', fn ($q) => $q->where('ma_sinh_vien', $sinhVien->id)->where('trang_thai', 'da_duyet'))
            ->first();

        if ($trungMon) {
            return response()->json([
                'message' => "Bạn đã đăng ký môn này ở lớp {$trungMon->ma_lop_hoc} — mỗi môn chỉ học một lớp.",
            ], 422);
        }

        // Điều kiện 2: không trùng lịch với các lớp đã đăng ký
        $maLopsDaDangKy = DangKyLopHoc::where('ma_sinh_vien', $sinhVien->id)
            ->where('trang_thai', 'da_duyet')
            ->pluck('ma_lop_hoc');

        $lichMoi = $lopHoc->lichHoc()->get();
        $lichCu = LichHoc::with('lopHoc.monHoc')
            ->whereIn('ma_lop_hoc', $maLopsDaDangKy)
            ->get();

        foreach ($lichMoi as $moi) {
            foreach ($lichCu as $cu) {
                if ($cu->ngay_hoc->equalTo($moi->ngay_hoc)
                    && $cu->gio_bat_dau < $moi->gio_ket_thuc
                    && $moi->gio_bat_dau < $cu->gio_ket_thuc) {
                    $tenMon = $cu->lopHoc?->monHoc?->ten_mon ?? $cu->lopHoc?->ten_lop;

                    return response()->json([
                        'message' => 'Trùng lịch học: trùng với môn "'.$tenMon.'" ngày '
                            .$moi->ngay_hoc->format('d/m/Y')
                            .' ('.$cu->gio_bat_dau->format('H:i').'–'.$cu->gio_ket_thuc->format('H:i').').',
                    ], 422);
                }
            }
        }

        // Nếu từng hủy thì kích hoạt lại bản ghi cũ (tránh trùng unique)
        DangKyLopHoc::updateOrCreate(
            ['ma_sinh_vien' => $sinhVien->id, 'ma_lop_hoc' => $lopHoc->id],
            ['ngay_dang_ky' => now()->toDateString(), 'trang_thai' => 'da_duyet']
        );

        return response()->json(['message' => 'Đăng ký lớp học thành công.']);
    }

    /** US04 - Hủy đăng ký lớp học: chỉ trong tuần học đầu tiên của kỳ (tuần học thử). */
    public function huyDangKy(Request $request, LopHoc $lopHoc): JsonResponse
    {
        $sinhVien = $request->user()->sinhVien;

        if (! $this->conTuanDauTien($lopHoc)) {
            return response()->json([
                'message' => 'Đã quá tuần học đầu tiên của kỳ — không thể hủy lớp.',
            ], 422);
        }

        $dangKy = DangKyLopHoc::where('ma_sinh_vien', $sinhVien->id)
            ->where('ma_lop_hoc', $lopHoc->id)
            ->where('trang_thai', '!=', 'huy')
            ->first();

        if (! $dangKy) {
            return response()->json(['message' => 'Bạn chưa đăng ký lớp này.'], 404);
        }

        $dangKy->update(['trang_thai' => 'huy']);

        return response()->json(['message' => 'Đã hủy đăng ký lớp học.']);
    }

    /** Danh sách lớp sinh viên đang theo học. */
    public function lopCuaToi(Request $request): JsonResponse
    {
        $sinhVien = $request->user()->sinhVien;

        $lops = LopHoc::with('monHoc')
            ->whereHas('dangKy', fn ($q) => $q->where('ma_sinh_vien', $sinhVien->id)->where('trang_thai', 'da_duyet'))
            ->with([
                'giangVienPhuTrach.taiKhoan' => fn ($q) => $q->select('id', 'ho_ten'),
                'lichHoc' => fn ($q) => $q->select('id', 'ma_lop_hoc', 'ngay_hoc', 'gio_bat_dau', 'gio_ket_thuc')
                    ->orderBy('ngay_hoc')->orderBy('gio_bat_dau'),
            ])
            ->get()
            ->map(fn (LopHoc $lop) => [
                'id' => $lop->id,
                'ma_lop_hoc' => $lop->ma_lop_hoc,
                'ten_lop' => $lop->ten_lop,
                'mon_hoc' => $lop->monHoc?->ten_mon,
                'hoc_ky' => $lop->hoc_ky,
                'nam_hoc' => $lop->nam_hoc,
                'trang_thai' => $lop->trang_thai,
                'giang_vien' => $lop->giangVienPhuTrach->first()?->taiKhoan?->ho_ten,
                'lich_hoc' => $lop->lichHoc->map(fn ($lich) => [
                    'id' => $lich->id,
                    'ngay_hoc' => $lich->ngay_hoc?->format('Y-m-d'),
                    'gio_bat_dau' => $lich->gio_bat_dau?->format('H:i'),
                    'gio_ket_thuc' => $lich->gio_ket_thuc?->format('H:i'),
                ]),
                // Chỉ được hủy trong tuần học thử đầu tiên
                'con_huy' => $this->conTuanDauTien($lop),
            ]);

        return response()->json(['danh_sach' => $lops]);
    }
}

