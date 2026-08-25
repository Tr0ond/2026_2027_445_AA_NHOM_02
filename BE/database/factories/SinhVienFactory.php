<?php

namespace Database\Factories;

use App\Models\SinhVien;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SinhVienFactory extends Factory
{
    protected $model = SinhVien::class;

    public function definition(): array
    {
        return [
            'ma_sinh_vien' => $this->faker->unique()->regexify('SV[0-9]{6}'),
            'ma_tai_khoan' => User::factory(),
            'lop_danh_nghia' => $this->faker->randomElement(['CNTT-K15', 'KT-K15', 'NNA-K15']),
            'khoa' => $this->faker->randomElement(['Cong nghe thong tin', 'Kinh te', 'Ngon ngu học']),
            'ngay_sinh' => $this->faker->date(),
            'gioi_tinh' => $this->faker->randomElement(['nam', 'nu']),
        ];
    }
}
