<?php

namespace Database\Factories;

use App\Models\DonXinPhep;
use Illuminate\Database\Eloquent\Factories\Factory;

class DonXinPhepFactory extends Factory
{
    protected $model = DonXinPhep::class;

    public function definition(): array
    {
        return [
            'ma_sinh_vien' => function () {
                return \App\Models\SinhVien::factory()->create()->id;
            },
            'ma_lop_hoc' => function () {
                return \App\Models\LopHoc::factory()->create()->id;
            },
            'ma_lich_hoc' => function () {
                return \App\Models\LichHoc::factory()->create()->id;
            },
            'ngay_nghi' => $this->faker->date(),
            'ly_do' => $this->faker->sentence(),
            'trang_thai' => $this->faker->randomElement(['cho_duyet', 'da_duyet', 'tu_choi']),
            'nguoi_duyet' => null,
            'thoi_gian_duyet' => null,
        ];
    }
}
