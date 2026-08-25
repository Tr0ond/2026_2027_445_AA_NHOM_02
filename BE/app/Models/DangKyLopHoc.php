<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DangKyLopHoc extends Model
{
    use HasFactory;
    
    protected $table = 'dang_ky_lop_hoc';

    protected $fillable = ['ma_sinh_vien', 'ma_lop_hoc', 'ngay_dang_ky', 'trang_thai'];

    protected function casts(): array
    {
        return ['ngay_dang_ky' => 'date'];
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
