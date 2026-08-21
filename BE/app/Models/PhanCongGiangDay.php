<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhanCongGiangDay extends Model
{
    protected $table = 'phan_cong_giang_day';

    protected $fillable = ['ma_giang_vien', 'ma_lop_hoc', 'vai_tro_phu_trach'];

    public function giangVien()
    {
        return $this->belongsTo(GiangVien::class, 'ma_giang_vien');
    }

    public function lopHoc()
    {
        return $this->belongsTo(LopHoc::class, 'ma_lop_hoc');
    }
}
