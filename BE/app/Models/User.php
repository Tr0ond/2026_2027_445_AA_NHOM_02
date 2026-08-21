<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property string $ho_ten
 * @property string $email
 * @property string $mat_khau
 * @property string $vai_tro
 * @property string $trang_thai
 * @property string|null $anh_dai_dien
 * @property string|null $so_dien_thoai
 * @property string|null $dia_chi
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'tai_khoan';

    protected $fillable = [
        'ho_ten',
        'email',
        'mat_khau',
        'vai_tro',
        'trang_thai',
        'anh_dai_dien',
        'so_dien_thoai',
        'dia_chi',
    ];

    protected $hidden = [
        'mat_khau',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'mat_khau' => 'hashed',
        ];
    }

    public function sinhVien()
    {
        return $this->hasOne(SinhVien::class, 'ma_tai_khoan');
    }

    public function giangVien()
    {
        return $this->hasOne(GiangVien::class, 'ma_tai_khoan');
    }

    public function thongBao()
    {
        return $this->hasMany(ThongBao::class, 'ma_tai_khoan');
    }

    public function laAdmin(): bool
    {
        return $this->vai_tro === 'admin';
    }

    public function laGiangVien(): bool
    {
        return $this->vai_tro === 'giang_vien';
    }

    public function laSinhVien(): bool
    {
        return $this->vai_tro === 'sinh_vien';
    }
}
