<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exports\BaoCaoDiemDanhExport;
use App\Exports\BaoCaoDiemExport;
use App\Http\Controllers\Controller;
use App\Models\ChiTietDiemDanh;
use App\Models\DangKyLopHoc;
use App\Models\LopHoc;
use App\Models\PhienDiemDanh;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/** US17 - Xuất báo cáo điểm danh; US24 - xuất báo cáo điểm số. */
class BaoCaoController extends Controller
{
    /** Tổng hợp điểm danh một lớp (JSON, dùng xem trước trên web). */
    public function tongHopDiemDanh(Request $request, LopHoc $lopHoc): JsonResponse
    {
        $phienIds = PhienDiemDanh::whereHas('lichHoc', fn ($q) => $q->where('ma_lop_hoc', $lopHoc->id))
            ->where('trang_thai', 'da_dong')
            ->pluck('id');

        $tongPhien = $phienIds->count();

        $sinhViens = DangKyLopHoc::with('sinhVien.taiKhoan')
            ->where('ma_lop_hoc', $lopHoc->id)
            ->where('trang_thai', 'da_duyet')
            ->get();

        $danhSach = $sinhViens->map(function ($dk) use ($phienIds, $tongPhien) {
            $chiTiet = ChiTietDiemDanh::whereIn('ma_phien_diem_danh', $phienIds)
                ->where('ma_sinh_vien', $dk->ma_sinh_vien)
                ->get();

            $coMat = $chiTiet->whereIn('trang_thai_diem_danh', ['co_mat', 'di_muon'])->count();
            $vang = $chiTiet->where('trang_thai_diem_danh', 'vang')->count();
            $xinPhep = $chiTiet->where('trang_thai_diem_danh', 'xin_phep')->count();

            return [
                'ma_sv_text' => $dk->sinhVien?->ma_sinh_vien,
                'ho_ten' => $dk->sinhVien?->taiKhoan?->ho_ten,
                'so_buoi' => $tongPhien,
                'so_co_mat' => $coMat,
                'so_vang' => $vang,
                'so_xin_phep' => $xinPhep,
                'ty_le_chuyen_can' => $tongPhien > 0 ? round($coMat / $tongPhien * 100, 1) : 0,
            ];
        });

        return response()->json([
            'lop_hoc' => $lopHoc->only(['id', 'ma_lop_hoc', 'ten_lop']),
            'so_phien_diem_danh' => $tongPhien,
            'danh_sach' => $danhSach,
        ]);
    }

    /** US17 - Tải Excel báo cáo điểm danh lớp. */
    public function xuatDiemDanh(Request $request, LopHoc $lopHoc): BinaryFileResponse
    {
        return Excel::download(
            new BaoCaoDiemDanhExport($lopHoc),
            'diem-danh-'.$lopHoc->ma_lop_hoc.'-'.now()->format('Ymd').'.xlsx'
        );
    }

    /** US24 - Tải Excel bảng điểm lớp. */
    public function xuatDiem(Request $request, LopHoc $lopHoc): BinaryFileResponse
    {
        return Excel::download(
            new BaoCaoDiemExport($lopHoc),
            'diem-'.$lopHoc->ma_lop_hoc.'-'.now()->format('Ymd').'.xlsx'
        );
    }
}
