<?php

namespace Database\Factories;

use App\Enums\VocabularyKind;
use App\Models\Lesson;
use App\Models\VocabularyItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VocabularyItem>
 */
class VocabularyItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => 'vocab-'.fake()->unique()->numberBetween(1, 999999),
            'lesson_id' => Lesson::factory(),
            'term' => fake()->word(),
            'kind' => VocabularyKind::Word,
            'meaning' => ['en' => fake()->sentence(3), 'es' => fake()->sentence(3)],
            'example' => fake()->sentence(),
            'position' => fake()->unique()->numberBetween(1, 30000),
        ];
    }
}
