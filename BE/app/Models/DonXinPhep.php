<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DonXinPhep extends Model
{
    use HasFactory;
    
    protected $table = 'don_xin_phep';

    protected $fillable = [
        'ma_sinh_vien', 'ma_lop_hoc', 'ma_lich_hoc', 'ngay_nghi', 'ly_do', 'trang_thai', 'nguoi_duyet', 'thoi_gian_duyet',
    ];

    protected function casts(): array
    {
        return [
            'ngay_nghi' => 'date',
            'thoi_gian_duyet' => 'datetime',
        ];
    }

    public function sinhVien()
    {
        return $this->belongsTo(SinhVien::class, 'ma_sinh_vien');
    }

    public function lopHoc()
    {
        return $this->belongsTo(LopHoc::class, 'ma_lop_hoc');
    }

    public function lichHoc()
    {
        return $this->belongsTo(LichHoc::class, 'ma_lich_hoc');
    }

    public function nguoiDuyet()
    {
        return $this->belongsTo(User::class, 'nguoi_duyet');
    }
}
