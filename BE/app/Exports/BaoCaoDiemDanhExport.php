<?php

namespace App\Exports;

use App\Models\ChiTietDiemDanh;
use App\Models\DangKyLopHoc;
use App\Models\LopHoc;
use App\Models\PhienDiemDanh;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class BaoCaoDiemDanhExport implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize
{
    public function __construct(protected LopHoc $lopHoc) {}

    public function collection(): \Illuminate\Support\Collection
    {
        $phienIds = PhienDiemDanh::whereHas('lichHoc', fn ($q) => $q->where('ma_lop_hoc', $this->lopHoc->id))
            ->where('trang_thai', 'da_dong')
            ->orderBy('thoi_gian_bat_dau')
            ->pluck('id');

        $sinhViens = DangKyLopHoc::with('sinhVien.taiKhoan')
            ->where('ma_lop_hoc', $this->lopHoc->id)
            ->where('trang_thai', 'da_duyet')
            ->get();

        $rows = [];
        foreach ($sinhViens as $dk) {
            $row = [
                $dk->sinhVien?->ma_sinh_vien,
                $dk->sinhVien?->taiKhoan?->ho_ten,
            ];
            $soCoMat = 0;
            $soVang = 0;
            $soXinPhep = 0;

            foreach ($phienIds as $phienId) {
                $ct = ChiTietDiemDanh::where('ma_phien_diem_danh', $phienId)
                    ->where('ma_sinh_vien', $dk->ma_sinh_vien)
                    ->first();

                $trangThai = match ($ct->trang_thai_diem_danh ?? 'vang') {
                    'co_mat' => 'X',
                    'di_muon' => 'M',
                    'xin_phep', 'vang_co_phep' => 'P',
                    default => 'V',
                };

                $trangThaiGoc = $ct->trang_thai_diem_danh ?? 'vang';
                if (in_array($trangThaiGoc, ['co_mat', 'di_muon'], true)) {
                    $soCoMat++;
                } elseif (in_array($trangThaiGoc, ['xin_phep', 'vang_co_phep'], true)) {
                    $soXinPhep++;
                } else {
                    $soVang++;
                }

                $row[] = $trangThai;
            }

            $row[] = $soCoMat;
            $row[] = $soVang;
            $row[] = $soXinPhep;
            $row[] = $phienIds->count() > 0 ? round($soCoMat / $phienIds->count() * 100, 1).'%' : '-';
            $rows[] = $row;
        }

        return collect($rows);
    }

    public function headings(): array
    {
        $phienThoiGian = PhienDiemDanh::whereHas('lichHoc', fn ($q) => $q->where('ma_lop_hoc', $this->lopHoc->id))
            ->where('trang_thai', 'da_dong')
            ->orderBy('thoi_gian_bat_dau')
            ->get();

        $headings = ['Mã SV', 'Họ tên'];

        foreach ($phienThoiGian as $phien) {
            $headings[] = $phien->thoi_gian_bat_dau->format('d/m H:i');
        }

        $headings[] = 'Số buổi có mặt';
        $headings[] = 'Số buổi vắng';
        $headings[] = 'Số buổi xin phép';
        $headings[] = 'Tỷ lệ chuyên cần';

        return $headings;
    }

    public function title(): string
    {
        return 'Báo cáo điểm danh '.$this->lopHoc->ma_lop_hoc;
    }
}
