<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiemSinhVien extends Model
{
    protected $table = 'diem_sinh_vien';

    protected $fillable = ['ma_sinh_vien', 'ma_thanh_phan', 'diem'];

    protected function casts(): array
    {
        return ['diem' => 'decimal:2'];
    }

    public function sinhVien()
    {
        return $this->belongsTo(SinhVien::class, 'ma_sinh_vien');
    }

    public function thanhPhan()
    {
        return $this->belongsTo(DiemThanhPhan::class, 'ma_thanh_phan');
    }
}
