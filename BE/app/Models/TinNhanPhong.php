<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TinNhanPhong extends Model
{
    protected $table = 'tin_nhan_phong';

    protected $fillable = ['ma_phong_hoc_truc_tuyen', 'ma_tai_khoan', 'noi_dung', 'thoi_gian_gui'];

    protected function casts(): array
    {
        return ['thoi_gian_gui' => 'datetime'];
    }

    public function taiKhoan()
    {
        return $this->belongsTo(User::class, 'ma_tai_khoan');
    }

    public function phong()
    {
        return $this->belongsTo(PhongHocTrucTuyen::class, 'ma_phong_hoc_truc_tuyen');
    }
}
