<?php

namespace Database\Factories;

use App\Models\Passage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Passage>
 */
class PassageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => 'passage-'.fake()->unique()->numberBetween(1, 999999),
            'title' => fake()->words(3, true),
            'body' => fake()->paragraphs(2, true),
            'transcript' => null,
            'audio_path' => null,
        ];
    }

    /** A reading passage: Markdown body, no audio. */
    public function reading(): static
    {
        return $this->state(fn () => [
            'body' => fake()->paragraphs(3, true),
            'transcript' => null,
            'audio_path' => null,
        ]);
    }

    /** A listening passage: audio and a hidden transcript, no body. */
    public function listening(): static
    {
        return $this->state(fn () => [
            'body' => null,
            'transcript' => fake()->paragraph(),
            'audio_path' => 'a1/listening-'.fake()->unique()->numberBetween(1, 999999).'.mp3',
        ]);
    }
}
