<?php

namespace App\Services;

use App\Models\DiemSinhVien;
use App\Models\DiemThanhPhan;
use App\Models\KetQuaHocPhan;
use App\Models\LopHoc;

class KetQuaHocPhanService
{
    public function dongBo(LopHoc $lopHoc, int $maSinhVien): KetQuaHocPhan
    {
        $thanhPhans = DiemThanhPhan::where('ma_lop_hoc', $lopHoc->id)->get();
        $diem = DiemSinhVien::where('ma_sinh_vien', $maSinhVien)
            ->whereIn('ma_thanh_phan', $thanhPhans->pluck('id'))
            ->get()
            ->keyBy('ma_thanh_phan');

        $tongDiem = 0;
        $tongTrongSo = 0;

        foreach ($thanhPhans as $thanhPhan) {
            $diemThanhPhan = $diem->get($thanhPhan->id)?->diem;
            if ($diemThanhPhan === null) {
                continue;
            }

            $tongDiem += (float) $diemThanhPhan * (float) $thanhPhan->trong_so;
            $tongTrongSo += (float) $thanhPhan->trong_so;
        }

        $diemTongKet = $tongTrongSo > 0 ? round($tongDiem / $tongTrongSo, 2) : null;

        return KetQuaHocPhan::updateOrCreate(
            [
                'ma_sinh_vien' => $maSinhVien,
                'ma_lop_hoc' => $lopHoc->id,
            ],
            [
                'diem_tong_ket' => $diemTongKet,
                'xep_loai' => $this->xepLoai($diemTongKet),
                'trang_thai' => $diemTongKet !== null && $diemTongKet >= 5 ? 'dat' : 'hoc_lai',
                'thoi_gian_cap_nhat' => now(),
            ]
        );
    }

    private function xepLoai(?float $diem): ?string
    {
        if ($diem === null) {
            return null;
        }

        return match (true) {
            $diem >= 8.5 => 'A',
            $diem >= 7.0 => 'B',
            $diem >= 5.5 => 'C',
            $diem >= 4.0 => 'D',
            default => 'F',
        };
    }
}

