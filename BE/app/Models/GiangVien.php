<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GiangVien extends Model
{
    use HasFactory;
    
    protected $table = 'giang_vien';

    protected $fillable = ['ma_giang_vien', 'ma_tai_khoan', 'hoc_vi', 'bo_mon'];

    public function taiKhoan()
    {
        return $this->belongsTo(User::class, 'ma_tai_khoan');
    }

    public function phanCong()
    {
        return $this->hasMany(PhanCongGiangDay::class, 'ma_giang_vien');
    }
}
