<?php

namespace Database\Factories;

use App\Models\DiemThanhPhan;
use Illuminate\Database\Eloquent\Factories\Factory;

class DiemThanhPhanFactory extends Factory
{
    protected $model = DiemThanhPhan::class;

    public function definition(): array
    {
        return [
            'ma_lop_hoc' => function () {
                return \App\Models\LopHoc::factory()->create()->id;
            },
            'ten_thanh_phan' => $this->faker->randomElement(['Giua ky', 'Cuoi ky', 'Bai tap', 'Diem danh']),
            'trong_so' => $this->faker->randomFloat(1, 0.1, 0.4),
        ];
    }
}
