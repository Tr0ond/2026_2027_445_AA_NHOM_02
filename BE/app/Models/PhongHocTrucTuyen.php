<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhongHocTrucTuyen extends Model
{
    protected $table = 'phong_hoc_truc_tuyen';

    protected $fillable = ['ma_phong', 'ma_lich_hoc', 'duong_dan_tham_gia', 'nen_tang', 'trang_thai'];

    public function lichHoc()
    {
        return $this->belongsTo(LichHoc::class, 'ma_lich_hoc');
    }

    public function thanhVien()
    {
        return $this->hasMany(ThanhVienPhongTrucTuyen::class, 'ma_phong_hoc_truc_tuyen');
    }

    public function tinNhan()
    {
        return $this->hasMany(TinNhanPhong::class, 'ma_phong_hoc_truc_tuyen');
    }

    public function phienDiemDanh()
    {
        return $this->hasManyThrough(
            PhienDiemDanh::class,
            LichHoc::class,
            'id',
            'ma_lich_hoc',
            'ma_lich_hoc',
            'id'
        );
    }
}
