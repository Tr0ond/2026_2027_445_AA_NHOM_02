<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaQrToken extends Model
{
    protected $table = 'ma_qr_token';

    protected $fillable = ['ma_phien', 'token', 'het_han_luc'];

    protected function casts(): array
    {
        return ['het_han_luc' => 'datetime'];
    }

    public function phien()
    {
        return $this->belongsTo(PhienDiemDanh::class, 'ma_phien');
    }

    public function conHan(): bool
    {
        return $this->het_han_luc?->isFuture() ?? false;
    }
}
