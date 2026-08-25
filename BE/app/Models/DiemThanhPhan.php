<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiemThanhPhan extends Model
{
    use HasFactory;
    
    protected $table = 'diem_thanh_phan';

    protected $fillable = ['ma_lop_hoc', 'ten_thanh_phan', 'trong_so'];

    protected function casts(): array
    {
        return ['trong_so' => 'decimal:2'];
    }

    public function lopHoc()
    {
        return $this->belongsTo(LopHoc::class, 'ma_lop_hoc');
    }

    public function diemSinhVien()
    {
        return $this->hasMany(DiemSinhVien::class, 'ma_thanh_phan');
    }

    /** Thành phần được cấu hình trực tiếp cho lớp học. */
    public static function cuaLop(LopHoc $lopHoc)
    {
        return self::where('ma_lop_hoc', $lopHoc->id);
    }
}
