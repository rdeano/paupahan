<?php

namespace Database\Factories;

use App\Models\Barangay;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Barangay>
 */
class BarangayFactory extends Factory
{
    public function definition(): array
    {
        return [
            'psgc_code' => (string) fake()->unique()->numberBetween(1000000000, 9999999999),
            'name' => 'Barangay '.fake()->unique()->lastName(),
            'district_id' => null,
        ];
    }
}
