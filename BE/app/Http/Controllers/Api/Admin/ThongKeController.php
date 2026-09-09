<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChiTietDiemDanh;
use App\Models\DiemSinhVien;
use App\Models\DonXinPhep;
use App\Models\LopHoc;
use App\Models\MonHoc;
use App\Models\PhienDiemDanh;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** US24 - Thống kê, báo cáo hệ thống. */
class ThongKeController extends Controller
{
    public function tongQuan(): JsonResponse
    {
        $tongTaiKhoan = User::count();
        $tongSinhVien = User::where('vai_tro', 'sinh_vien')->count();
        $tongGiangVien = User::where('vai_tro', 'giang_vien')->count();
        $tongMonHoc = MonHoc::count();
        $tongLopHoc = LopHoc::count();
        $tongPhien = PhienDiemDanh::count();

        $tongChiTiet = ChiTietDiemDanh::count();
        $tongCoMat = ChiTietDiemDanh::whereIn('trang_thai_diem_danh', ['co_mat', 'di_muon'])->count();

        return response()->json([
            'tai_khoan' => [
                'tong' => $tongTaiKhoan,
                'sinh_vien' => $tongSinhVien,
                'giang_vien' => $tongGiangVien,
                'bi_khoa' => User::where('trang_thai', 'khoa')->count(),
            ],
            'mon_hoc' => $tongMonHoc,
            'lop_hoc' => $tongLopHoc,
            'phien_diem_danh' => $tongPhien,
            'ty_le_diem_danh_tb' => $tongChiTiet > 0 ? round($tongCoMat / $tongChiTiet * 100, 1) : 0,
            'don_xin_phep_cho_duyet' => DonXinPhep::where('trang_thai', 'cho_duyet')->count(),
        ]);
    }

    /** Tỷ lệ chuyên cần trung bình theo từng lớp. */
    public function chuyenCanTheoLop(): JsonResponse
    {
        $lops = LopHoc::withCount(['dangKy as so_sinh_vien' => fn ($q) => $q->where('trang_thai', 'da_duyet')])
            ->orderBy('ten_lop')
            ->get();

        $duLieu = $lops->map(function ($lop) {
            $phienIds = PhienDiemDanh::whereHas('lichHoc', fn ($q) => $q->where('ma_lop_hoc', $lop->id))
                ->where('trang_thai', 'da_dong')
                ->pluck('id');

            $tong = ChiTietDiemDanh::whereIn('ma_phien_diem_danh', $phienIds)->count();
            $coMat = ChiTietDiemDanh::whereIn('ma_phien_diem_danh', $phienIds)
                ->whereIn('trang_thai_diem_danh', ['co_mat', 'di_muon'])
                ->count();

            return [
                'lop' => $lop->ten_lop,
                'ma_lop' => $lop->ma_lop_hoc,
                'so_sinh_vien' => $lop->so_sinh_vien,
                'so_phien' => $phienIds->count(),
                'ty_le' => $tong > 0 ? round($coMat / $tong * 100, 1) : 0,
            ];
        });

        return response()->json(['danh_sach' => $duLieu]);
    }

    /** Điểm trung bình theo từng lớp. */
    public function diemTheoLop(): JsonResponse
    {
        $lops = LopHoc::with('monHoc')->withCount(['dangKy as so_sinh_vien' => fn ($q) => $q->where('trang_thai', 'da_duyet')])
            ->orderBy('ten_lop')
            ->get();

        $duLieu = $lops->map(function ($lop) {
            // Thành phần áp dụng cho lớp: mức môn (dùng chung) + mức lớp cũ
            $maThanhPhan = \App\Models\DiemThanhPhan::cuaLop($lop)->pluck('id');

            // Tính điểm tổng kết bình quân theo trọng số từng sinh viên rồi lấy trung bình lớp
            $tongLop = 0;
            $soSinhVienCoDiem = 0;

            $maSinhViens = $lop->dangKy()->where('trang_thai', 'da_duyet')->pluck('ma_sinh_vien');
            foreach ($maSinhViens as $maSV) {
                $ds = DiemSinhVien::with('thanhPhan')
                    ->where('ma_sinh_vien', $maSV)
                    ->whereIn('ma_thanh_phan', $maThanhPhan)
                    ->whereNotNull('diem')
                    ->get();

                $tong = 0;
                $trongSo = 0;
                foreach ($ds as $d) {
                    $tong += (float) $d->diem * (float) $d->thanhPhan->trong_so;
                    $trongSo += (float) $d->thanhPhan->trong_so;
                }

                if ($trongSo > 0) {
                    $tongLop += $tong / $trongSo;
                    $soSinhVienCoDiem++;
                }
            }

            return [
                'lop' => $lop->ten_lop,
                'ma_lop' => $lop->ma_lop_hoc,
                'mon_hoc' => $lop->monHoc?->ten_mon,
                'so_sinh_vien' => $lop->so_sinh_vien,
                'diem_tb' => $soSinhVienCoDiem > 0 ? round($tongLop / $soSinhVienCoDiem, 2) : null,
            ];
        });

        return response()->json(['danh_sach' => $duLieu]);
    }
}
