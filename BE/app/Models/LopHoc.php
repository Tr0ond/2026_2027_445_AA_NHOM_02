<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LopHoc extends Model
{
    use HasFactory;
    
    protected $table = 'lop_hoc';

    protected $fillable = ['ma_lop_hoc', 'ten_lop', 'ma_mon_hoc', 'hoc_ky', 'nam_hoc', 'so_luong_toi_da', 'trang_thai'];

    public function monHoc()
    {
        return $this->belongsTo(MonHoc::class, 'ma_mon_hoc');
    }

    public function phanCong()
    {
        return $this->hasMany(PhanCongGiangDay::class, 'ma_lop_hoc');
    }

    public function giangVienPhuTrach()
    {
        return $this->belongsToMany(GiangVien::class, 'phan_cong_giang_day', 'ma_lop_hoc', 'ma_giang_vien')
            ->withPivot('vai_tro_phu_trach');
    }

    public function dangKy()
    {
        return $this->hasMany(DangKyLopHoc::class, 'ma_lop_hoc');
    }

    public function lichHoc()
    {
        return $this->hasMany(LichHoc::class, 'ma_lop_hoc');
    }

    public function diemThanhPhan()
    {
        return $this->hasMany(DiemThanhPhan::class, 'ma_lop_hoc');
    }

    public function ketQuaHocPhan()
    {
        return $this->hasMany(KetQuaHocPhan::class, 'ma_lop_hoc');
    }

    public function soLuongDaDangKy(): int
    {
        return $this->dangKy()->where('trang_thai', '!=', 'huy')->count();
    }
}
