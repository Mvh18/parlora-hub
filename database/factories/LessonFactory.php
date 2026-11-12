<?php

namespace Database\Factories;

use App\Enums\LessonType;
use App\Models\Lesson;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lesson>
 */
class LessonFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => 'lesson-'.fake()->unique()->numberBetween(1, 999999),
            'unit_id' => Unit::factory(),
            'type' => LessonType::Grammar,
            'title' => fake()->words(3, true),
            'theory' => ['en' => '## '.fake()->sentence(3)."\n\n".fake()->paragraph()],
            'position' => fake()->unique()->numberBetween(1, 30000),
        ];
    }
}
