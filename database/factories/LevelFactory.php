<?php

namespace Database\Factories;

use App\Enums\CefrLevel;
use App\Models\Level;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Level>
 */
class LevelFactory extends Factory
{
    /**
     * Define the model's default state: a distinct CEFR level each time (at
     * most five per test, one per level).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return $this->attributesFor(fake()->unique()->randomElement(CefrLevel::cases()));
    }

    /** A specific level, with its natural position and key. */
    public function code(CefrLevel $level): static
    {
        return $this->state(fn () => $this->attributesFor($level));
    }

    /** @return array<string, mixed> */
    private function attributesFor(CefrLevel $level): array
    {
        return [
            'key' => strtolower($level->value),
            'code' => $level,
            'title' => $level->value.' course',
            'position' => $level->position(),
            'pass_threshold' => 70,
        ];
    }
}
