<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KetQuaHocPhan extends Model
{
    protected $table = 'ket_qua_hoc_phan';

    protected $fillable = [
        'ma_sinh_vien', 'ma_lop_hoc', 'diem_tong_ket', 'xep_loai',
        'trang_thai', 'thoi_gian_cap_nhat',
    ];

    protected function casts(): array
    {
        return [
            'diem_tong_ket' => 'decimal:2',
            'thoi_gian_cap_nhat' => 'datetime',
        ];
    }

    public function sinhVien()
    {
        return $this->belongsTo(SinhVien::class, 'ma_sinh_vien');
    }

    public function lopHoc()
    {
        return $this->belongsTo(LopHoc::class, 'ma_lop_hoc');
    }
}
