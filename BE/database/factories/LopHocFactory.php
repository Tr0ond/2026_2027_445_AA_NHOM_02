<?php

namespace Database\Factories;

use App\Models\LopHoc;
use Illuminate\Database\Eloquent\Factories\Factory;

class LopHocFactory extends Factory
{
    protected $model = LopHoc::class;

    public function definition(): array
    {
        return [
            'ma_lop_hoc' => $this->faker->unique()->regexify('LH[0-9]{4}'),
            'ten_lop' => $this->faker->sentence(3),
            'ma_mon_hoc' => function () {
                return \App\Models\MonHoc::factory()->create()->id;
            },
            'hoc_ky' => $this->faker->numberBetween(1, 3),
            'nam_hoc' => '2024-2025',
            'so_luong_toi_da' => $this->faker->numberBetween(30, 60),
            'trang_thai' => 'dang_mo',
        ];
    }
}
