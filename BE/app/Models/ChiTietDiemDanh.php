<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChiTietDiemDanh extends Model
{
    protected $table = 'chi_tiet_diem_danh';

    protected $fillable = [
        'ma_phien_diem_danh', 'ma_sinh_vien', 'trang_thai_diem_danh',
        'thoi_gian_diem_danh', 'hinh_thuc_diem_danh',
    ];

    protected function casts(): array
    {
        return ['thoi_gian_diem_danh' => 'datetime'];
    }

    public function phien()
    {
        return $this->belongsTo(PhienDiemDanh::class, 'ma_phien_diem_danh');
    }

    public function sinhVien()
    {
        return $this->belongsTo(SinhVien::class, 'ma_sinh_vien');
    }
}
