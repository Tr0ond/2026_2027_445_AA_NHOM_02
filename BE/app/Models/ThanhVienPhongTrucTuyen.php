<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ThanhVienPhongTrucTuyen extends Model
{
    protected $table = 'thanh_vien_phong_truc_tuyen';

    protected $fillable = ['ma_phong_hoc_truc_tuyen', 'ma_tai_khoan', 'vai_tro', 'thoi_gian_tham_gia', 'thoi_gian_roi', 'gio_tay', 'duoc_phep_mac', 'duoc_phep_chia_se'];

    protected function casts(): array
    {
        return [
            'thoi_gian_tham_gia' => 'datetime',
            'thoi_gian_roi' => 'datetime',
            'gio_tay' => 'boolean',
            'duoc_phep_mac' => 'boolean',
            'duoc_phep_chia_se' => 'boolean',
        ];
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
