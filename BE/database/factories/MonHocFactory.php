<?php

namespace Database\Factories;

use App\Models\MonHoc;
use Illuminate\Database\Eloquent\Factories\Factory;

class MonHocFactory extends Factory
{
    protected $model = MonHoc::class;

    public function definition(): array
    {
        return [
            'ma_mon_hoc' => $this->faker->unique()->regexify('MH[0-9]{4}'),
            'ten_mon' => $this->faker->randomElement(['Lap trinh Web', 'Co so du lieu', 'Thuat toan', 'Mang may tinh']),
            'so_tin_chi' => $this->faker->numberBetween(2, 4),
        ];
    }
}
