<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ThongBao extends Model
{
    protected $table = 'thong_bao';

    protected $fillable = [
        'ma_tai_khoan', 'loai', 'tieu_de', 'noi_dung', 'da_doc',
        'thoi_gian_doc', 'du_lieu',
    ];

    protected function casts(): array
    {
        return [
            'da_doc' => 'boolean',
            'thoi_gian_doc' => 'datetime',
            'du_lieu' => 'array',
        ];
    }

    public function taiKhoan()
    {
        return $this->belongsTo(User::class, 'ma_tai_khoan');
    }
}
