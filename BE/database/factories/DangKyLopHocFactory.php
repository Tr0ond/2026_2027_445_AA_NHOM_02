<?php

namespace Database\Factories;

use App\Models\DangKyLopHoc;
use Illuminate\Database\Eloquent\Factories\Factory;

class DangKyLopHocFactory extends Factory
{
    protected $model = DangKyLopHoc::class;

    public function definition(): array
    {
        return [
            'ma_sinh_vien' => function () {
                return \App\Models\SinhVien::factory()->create()->id;
            },
            'ma_lop_hoc' => function () {
                return \App\Models\LopHoc::factory()->create()->id;
            },
            'ngay_dang_ky' => $this->faker->date(),
            'trang_thai' => $this->faker->randomElement(['da_dang_ky', 'huy']),
        ];
    }
}
