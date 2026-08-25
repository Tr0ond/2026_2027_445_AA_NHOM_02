<?php

namespace Database\Factories;

use App\Models\GiangVien;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class GiangVienFactory extends Factory
{
    protected $model = GiangVien::class;

    public function definition(): array
    {
        return [
            'ma_giang_vien' => $this->faker->unique()->regexify('GV[0-9]{6}'),
            'ma_tai_khoan' => User::factory(),
            'hoc_vi' => $this->faker->randomElement(['ThS', 'TS', 'PTS']),
            'bo_mon' => $this->faker->randomElement(['Cong nghe thong tin', 'Kinh te', 'Ngon ngu học']),
        ];
    }
}
