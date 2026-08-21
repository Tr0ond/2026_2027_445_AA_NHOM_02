<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhienDiemDanh extends Model
{
    protected $table = 'phien_diem_danh';

    protected $fillable = [
        'ma_phien', 'ma_lich_hoc', 'ma_giang_vien',
        'thoi_gian_bat_dau', 'thoi_gian_ket_thuc', 'hinh_thuc_diem_danh', 'trang_thai',
    ];

    protected function casts(): array
    {
        return [
            'thoi_gian_bat_dau' => 'datetime',
            'thoi_gian_ket_thuc' => 'datetime',
        ];
    }

    public function lichHoc()
    {
        return $this->belongsTo(LichHoc::class, 'ma_lich_hoc');
    }

    public function giangVien()
    {
        return $this->belongsTo(GiangVien::class, 'ma_giang_vien');
    }

    public function chiTiet()
    {
        return $this->hasMany(ChiTietDiemDanh::class, 'ma_phien_diem_danh');
    }

    public function qrTokens()
    {
        return $this->hasMany(MaQrToken::class, 'ma_phien');
    }

    public function conMo(): bool
    {
        return $this->trang_thai === 'dang_mo'
            && now()->between($this->thoi_gian_bat_dau, $this->thoi_gian_ket_thuc);
    }
}
