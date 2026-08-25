<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonHoc extends Model
{
    use HasFactory;
    
    protected $table = 'mon_hoc';

    protected $fillable = ['ma_mon_hoc', 'ten_mon', 'so_tin_chi', 'mo_ta'];

    public function lopHoc()
    {
        return $this->hasMany(LopHoc::class, 'ma_mon_hoc');
    }
}
