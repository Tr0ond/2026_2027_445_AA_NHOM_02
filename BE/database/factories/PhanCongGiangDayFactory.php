<?php

namespace Database\Factories;

use App\Models\PhanCongGiangDay;
use Illuminate\Database\Eloquent\Factories\Factory;

class PhanCongGiangDayFactory extends Factory
{
    protected $model = PhanCongGiangDay::class;

    public function definition(): array
    {
        return [
            'ma_giang_vien' => function () {
                return \App\Models\GiangVien::factory()->create()->id;
            },
            'ma_lop_hoc' => function () {
                return \App\Models\LopHoc::factory()->create()->id;
            },
            'vai_tro_phu_trach' => $this->faker->randomElement(['chinh', 'phu']),
        ];
    }
}
