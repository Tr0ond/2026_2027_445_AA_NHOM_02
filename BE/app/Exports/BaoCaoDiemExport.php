<?php

namespace App\Exports;

use App\Models\DiemSinhVien;
use App\Models\DiemThanhPhan;
use App\Models\DangKyLopHoc;
use App\Services\KetQuaHocPhanService;
use App\Models\LopHoc;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class BaoCaoDiemExport implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize
{
    protected $thanhPhan;

    public function __construct(protected LopHoc $lopHoc)
    {
        // Thành phần được cấu hình trực tiếp cho lớp.
        $this->thanhPhan = DiemThanhPhan::cuaLop($lopHoc)->orderBy('id')->get();
    }

    public function collection(): \Illuminate\Support\Collection
    {
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

            foreach ($this->thanhPhan as $tp) {
                $d = DiemSinhVien::where('ma_sinh_vien', $dk->ma_sinh_vien)
                    ->where('ma_thanh_phan', $tp->id)
                    ->value('diem');

                $row[] = $d ?? '-';

            }

            $ketQua = app(KetQuaHocPhanService::class)->dongBo($this->lopHoc, $dk->ma_sinh_vien);
            $row[] = $ketQua->diem_tong_ket ?? '-';
            $row[] = $ketQua->xep_loai ?? '-';
            $row[] = $ketQua->trang_thai === 'dat' ? 'Đạt' : 'Học lại';
            $rows[] = $row;
        }

        return collect($rows);
    }

    public function headings(): array
    {
        $headings = ['Mã SV', 'Họ tên'];

        foreach ($this->thanhPhan as $tp) {
            $headings[] = $tp->ten_thanh_phan.' ('.$tp->trong_so.')';
        }

        $headings[] = 'Điểm tổng kết';
        $headings[] = 'Xếp loại';
        $headings[] = 'Trạng thái';

        return $headings;
    }

    public function title(): string
    {
        return 'Bảng điểm '.$this->lopHoc->ma_lop_hoc;
    }
}
