<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LichHoc;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LichHocController extends Controller
{
    /**
     * US05 - Xem lịch học. Với sinh viên: lịch các lớp đã đăng ký.
     * Với giảng viên: lịch các lớp được phân công. Hỗ trợ lọc theo tuần (tuan = YYYY-MM-DD thứ 2).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $tuDien = $request->date('tu_ngay');
        $denNgay = $request->date('den_ngay');

        $query = LichHoc::with(['lopHoc.monHoc', 'phongTrucTuyen', 'lopHoc.giangVienPhuTrach.taiKhoan'])
            ->orderBy('ngay_hoc')
            ->orderBy('gio_bat_dau');

        if ($user->laSinhVien() && $user->sinhVien) {
            $query->whereHas('lopHoc.dangKy', fn ($q) => $q
                ->where('ma_sinh_vien', $user->sinhVien->id)
                ->where('trang_thai', 'da_duyet'));
        } elseif ($user->laGiangVien() && $user->giangVien) {
            $query->whereHas('lopHoc.phanCong', fn ($q) => $q
                ->where('ma_giang_vien', $user->giangVien->id));
        }

        if ($tuDien && $denNgay) {
            $query->whereBetween('ngay_hoc', [$tuDien, $denNgay]);
        }

        $danhSach = $query->get()->map(fn (LichHoc $lich) => [
            'id' => $lich->id,
            'ma_lop_hoc' => $lich->ma_lop_hoc,
            'ten_lop' => $lich->lopHoc?->ten_lop,
            'mon_hoc' => $lich->lopHoc?->monHoc?->ten_mon,
            'ma_mon_hoc' => $lich->lopHoc?->monHoc?->id,
            'giang_vien' => $lich->lopHoc?->giangVienPhuTrach
                ->map(fn ($gv) => $gv->taiKhoan?->ho_ten)
                ->filter()
                ->first(),
            'ngay_hoc' => $lich->ngay_hoc?->format('Y-m-d'),
            'gio_bat_dau' => $lich->gio_bat_dau?->format('H:i'),
            'gio_ket_thuc' => $lich->gio_ket_thuc?->format('H:i'),
            'phong_hoc' => $lich->phong_hoc,
            'co_hoc_truc_tuyen' => $lich->co_hoc_truc_tuyen,
            'chu_de' => $lich->chu_de,
            'trang_thai' => $lich->trang_thai,
            'phong_truc_tuyen' => $lich->phongTrucTuyen ? [
                'ma_phong' => $lich->phongTrucTuyen->ma_phong,
                'trang_thai' => $lich->phongTrucTuyen->trang_thai,
                'duong_dan' => $lich->phongTrucTuyen->duong_dan_tham_gia,
            ] : null,
        ]);

        return response()->json(['danh_sach' => $danhSach]);
    }

    public function show(LichHoc $lichHoc): JsonResponse
    {
        $lichHoc->load(['lopHoc.monHoc', 'phongTrucTuyen']);

        return response()->json(['lich_hoc' => [
            'id' => $lichHoc->id,
            'ten_lop' => $lichHoc->lopHoc?->ten_lop,
            'mon_hoc' => $lichHoc->lopHoc?->monHoc?->ten_mon,
            'ngay_hoc' => $lichHoc->ngay_hoc?->format('Y-m-d'),
            'gio_bat_dau' => $lichHoc->gio_bat_dau?->format('H:i'),
            'gio_ket_thuc' => $lichHoc->gio_ket_thuc?->format('H:i'),
            'co_hoc_truc_tuyen' => $lichHoc->co_hoc_truc_tuyen,
            'trang_thai' => $lichHoc->trang_thai,
            'phong_truc_tuyen' => $lichHoc->phongTrucTuyen ? [
                'ma_phong' => $lichHoc->phongTrucTuyen->ma_phong,
                'trang_thai' => $lichHoc->phongTrucTuyen->trang_thai,
                'duong_dan' => $lichHoc->phongTrucTuyen->duong_dan_tham_gia,
            ] : null,
        ]]);
    }
}

