<?php

namespace Database\Factories;

use App\Enums\PropertyStatus;
use App\Enums\PropertyType;
use App\Models\Barangay;
use App\Models\Property;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Property>
 */
class PropertyFactory extends Factory
{
    public function definition(): array
    {
        $type = fake()->randomElement(PropertyType::cases());

        return [
            'owner_id' => User::factory(),
            'title' => ucfirst(fake()->words(3, true)).' '.$type->label(),
            'type' => $type,
            'description' => fake()->paragraphs(2, true),
            'price_monthly' => fake()->numberBetween(15, 300) * 100,
            'deposit_months' => fake()->numberBetween(0, 2),
            'advance_months' => fake()->numberBetween(0, 2),
            'bedrooms' => fake()->numberBetween(0, 3),
            'bathrooms' => fake()->numberBetween(1, 2),
            'barangay_id' => fn () => Barangay::inRandomOrder()->value('id') ?? Barangay::factory(),
            'landmark' => null,
            'status' => PropertyStatus::Draft,
        ];
    }

    public function available(): static
    {
        return $this->state(['status' => PropertyStatus::Available]);
    }
}
