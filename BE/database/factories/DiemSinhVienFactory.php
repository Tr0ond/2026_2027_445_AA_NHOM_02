<?php

namespace Database\Factories;

use App\Models\DiemSinhVien;
use Illuminate\Database\Eloquent\Factories\Factory;

class DiemSinhVienFactory extends Factory
{
    protected $model = DiemSinhVien::class;

    public function definition(): array
    {
        return [
            'ma_sinh_vien' => function () {
                return \App\Models\SinhVien::factory()->create()->id;
            },
            'ma_thanh_phan' => function () {
                return \App\Models\DiemThanhPhan::factory()->create()->id;
            },
            'diem' => $this->faker->randomFloat(1, 0, 10),
        ];
    }
}
