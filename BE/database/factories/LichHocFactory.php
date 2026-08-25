<?php

namespace Database\Factories;

use App\Models\LichHoc;
use Illuminate\Database\Eloquent\Factories\Factory;

class LichHocFactory extends Factory
{
    protected $model = LichHoc::class;

    public function definition(): array
    {
        return [
            'ma_lop_hoc' => function () {
                return \App\Models\LopHoc::factory()->create()->id;
            },
            'ngay_hoc' => $this->faker->date(),
            'gio_bat_dau' => $this->faker->time(),
            'gio_ket_thuc' => $this->faker->time(),
            'phong_hoc' => $this->faker->bothify('PH###'),
        ];
    }
}
