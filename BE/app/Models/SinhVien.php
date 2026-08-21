<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SinhVien extends Model
{
    protected $table = 'sinh_vien';

    protected $fillable = ['ma_sinh_vien', 'ma_tai_khoan', 'lop_danh_nghia', 'khoa', 'ngay_sinh', 'gioi_tinh'];

    public function taiKhoan()
    {
        return $this->belongsTo(User::class, 'ma_tai_khoan');
    }

    public function dangKyLopHoc()
    {
        return $this->hasMany(DangKyLopHoc::class, 'ma_sinh_vien');
    }

    public function diemSinhVien()
    {
        return $this->hasMany(DiemSinhVien::class, 'ma_sinh_vien');
    }

    public function ketQuaHocPhan()
    {
        return $this->hasMany(KetQuaHocPhan::class, 'ma_sinh_vien');
    }
}
