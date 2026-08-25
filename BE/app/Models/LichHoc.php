<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LichHoc extends Model
{
    use HasFactory;
    
    protected $table = 'lich_hoc';

    protected $fillable = [
        'ma_lop_hoc', 'ngay_hoc', 'gio_bat_dau', 'gio_ket_thuc',
        'phong_hoc', 'co_hoc_truc_tuyen', 'chu_de', 'trang_thai',
    ];

    protected function casts(): array
    {
        return [
            'ngay_hoc' => 'date',
            'gio_bat_dau' => 'datetime:H:i',
            'gio_ket_thuc' => 'datetime:H:i',
            'co_hoc_truc_tuyen' => 'boolean',
        ];
    }

    public function lopHoc()
    {
        return $this->belongsTo(LopHoc::class, 'ma_lop_hoc');
    }

    public function phongTrucTuyen()
    {
        return $this->hasOne(PhongHocTrucTuyen::class, 'ma_lich_hoc');
    }

    public function phienDiemDanh()
    {
        return $this->hasMany(PhienDiemDanh::class, 'ma_lich_hoc');
    }

    public function donXinPhep()
    {
        return $this->hasMany(DonXinPhep::class, 'ma_lich_hoc');
    }
}
