<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\GiangVien;
use App\Models\LopHoc;
use App\Models\PhanCongGiangDay;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
            'ma_lop_hoc' => ['required', 'integer', 'exists:lop_hoc,id'],
            'vai_tro_phu_trach' => ['nullable', 'string', 'max:50'],
        ]);

        $tonTai = PhanCongGiangDay::where('ma_giang_vien', $data['ma_giang_vien'])
            ->where('ma_lop_hoc', $data['ma_lop_hoc'])
            ->exists();

        if ($tonTai) {
            return response()->json(['message' => 'Giảng viên này đã được phân công cho lớp.'], 422);
        }

        PhanCongGiangDay::create($data + ['vai_tro_phu_trach' => $data['vai_tro_phu_trach'] ?? 'giang_vien_chinh']);

        return response()->json(['message' => 'Đã phân công giảng dạy.'], 201);
    }

    public function destroy(Request $request, ?PhanCongGiangDay $phanCong = null): JsonResponse
    {
        // Hỗ trợ hủy theo cặp (ma_giang_vien, ma_lop_hoc) qua query string
        if ($request->filled('ma_giang_vien') && $request->filled('ma_lop_hoc')) {
            PhanCongGiangDay::where('ma_giang_vien', $request->ma_giang_vien)
                ->where('ma_lop_hoc', $request->ma_lop_hoc)
                ->delete();

            return response()->json(['message' => 'Đã hủy phân công.']);
        }

        $phanCong->delete();

        return response()->json(['message' => 'Đã hủy phân công.']);
    }
}
