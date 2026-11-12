<?php

namespace Database\Factories;

use App\Models\Level;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Unit>
 */
class UnitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => 'unit-'.fake()->unique()->numberBetween(1, 999999),
            'level_id' => Level::factory(),
            'title' => fake()->words(3, true),
            'position' => fake()->unique()->numberBetween(1, 30000),
        ];
    }
}
