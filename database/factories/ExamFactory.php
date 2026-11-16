<?php

namespace Database\Factories;

use App\Enums\ExamKind;
use App\Models\Exam;
use App\Models\Level;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Exam>
 */
class ExamFactory extends Factory
{
    /**
     * Define the model's default state: a level exam with its own level.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => 'exam-'.fake()->unique()->numberBetween(1, 999999),
            'kind' => ExamKind::Level,
            'level_id' => Level::factory(),
            'title' => fake()->words(3, true),
            'time_limit_minutes' => 60,
            'pass_score' => 70,
            'max_attempts' => null,
            'cooldown_hours' => 24,
        ];
    }

    /** A level exam for a specific level. */
    public function forLevel(Level $level): static
    {
        return $this->state(fn () => ['kind' => ExamKind::Level, 'level_id' => $level->id]);
    }

    /** The placement exam: no level. */
    public function placement(): static
    {
        return $this->state(fn () => ['kind' => ExamKind::Placement, 'level_id' => null]);
    }
}
