<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LichHoc;
use App\Models\LopHoc;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Lớp học giảng viên phụ trách (US12, US13, US18: chọn lớp để dạy/nhập điểm). */
class LopDayController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $giangVien = $request->user()->giangVien;

        $lops = LopHoc::with(['monHoc'])
            ->withCount([
                'dangKy as so_sinh_vien' => fn ($q) => $q->where('trang_thai', 'da_duyet'),
                'lichHoc as so_buoi_hoc',
            ])
            ->whereHas('phanCong', fn ($q) => $q->where('ma_giang_vien', $giangVien->id))
            ->orderByDesc('id')
            ->get()
            ->map(fn (LopHoc $lop) => [
                'id' => $lop->id,
                'ma_lop_hoc' => $lop->ma_lop_hoc,
                'ten_lop' => $lop->ten_lop,
                'mon_hoc' => $lop->monHoc?->ten_mon,
                'hoc_ky' => $lop->hoc_ky,
                'nam_hoc' => $lop->nam_hoc,
                'trang_thai' => $lop->trang_thai,
                'so_sinh_vien' => $lop->so_sinh_vien,
                'so_buoi_hoc' => $lop->so_buoi_hoc,
            ]);

        return response()->json(['danh_sach' => $lops]);
    }

    /** Các buổi học sắp tới / đang diễn ra của giảng viên. */
    public function buoiHoc(Request $request): JsonResponse
    {
        $giangVien = $request->user()->giangVien;

        $buois = LichHoc::with(['lopHoc.monHoc', 'phongTrucTuyen'])
            ->whereHas('lopHoc.phanCong', fn ($q) => $q->where('ma_giang_vien', $giangVien->id))
            ->where('ngay_hoc', '>=', now()->subDay()->toDateString())
            ->orderBy('ngay_hoc')
            ->orderBy('gio_bat_dau')
            ->limit(30)
            ->get()
            ->map(fn (LichHoc $lich) => [
                'id' => $lich->id,
                'ten_lop' => $lich->lopHoc?->ten_lop,
                'mon_hoc' => $lich->lopHoc?->monHoc?->ten_mon,
                'ngay_hoc' => $lich->ngay_hoc?->format('Y-m-d'),
                'gio_bat_dau' => $lich->gio_bat_dau?->format('H:i'),
                'gio_ket_thuc' => $lich->gio_ket_thuc?->format('H:i'),
                'co_hoc_truc_tuyen' => $lich->co_hoc_truc_tuyen,
                'trang_thai' => $lich->trang_thai,
                'phong' => $lich->phongTrucTuyen ? [
                    'ma_phong' => $lich->phongTrucTuyen->ma_phong,
                    'trang_thai' => $lich->phongTrucTuyen->trang_thai,
                ] : null,
            ]);

        return response()->json(['danh_sach' => $buois]);
    }
}

