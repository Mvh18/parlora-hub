<?php

namespace Database\Factories;

use App\Enums\ExerciseType;
use App\Enums\Skill;
use App\Models\Exercise;
use App\Models\Lesson;
use Database\Factories\Support\ExerciseSamples;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Exercise>
 */
class ExerciseFactory extends Factory
{
    /**
     * Define the model's default state: a valid multiple-choice exercise.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => 'exercise-'.fake()->unique()->numberBetween(1, 999999),
            'lesson_id' => Lesson::factory(),
            'passage_id' => null,
            'type' => ExerciseType::MultipleChoice,
            'skill' => Skill::Grammar,
            'prompt' => 'Choose the correct form of the verb.',
            'content' => ExerciseSamples::content(ExerciseType::MultipleChoice),
            'solution' => ExerciseSamples::solution(ExerciseType::MultipleChoice),
            'audio_path' => null,
            'position' => fake()->unique()->numberBetween(1, 30000),
        ];
    }

    /** A valid exercise of the given type, with a matching skill. */
    public function ofType(ExerciseType $type): static
    {
        return $this->state(fn () => [
            'type' => $type,
            'skill' => self::skillFor($type),
            'content' => ExerciseSamples::content($type),
            'solution' => ExerciseSamples::solution($type),
        ]);
    }

    public function multipleChoice(): static
    {
        return $this->ofType(ExerciseType::MultipleChoice);
    }

    public function fillGap(): static
    {
        return $this->ofType(ExerciseType::FillGap);
    }

    public function reorder(): static
    {
        return $this->ofType(ExerciseType::Reorder);
    }

    public function matching(): static
    {
        return $this->ofType(ExerciseType::Matching);
    }

    public function transformation(): static
    {
        return $this->ofType(ExerciseType::Transformation);
    }

    public function dictation(): static
    {
        return $this->ofType(ExerciseType::Dictation);
    }

    public function writing(): static
    {
        return $this->ofType(ExerciseType::Writing);
    }

    public function speaking(): static
    {
        return $this->ofType(ExerciseType::Speaking);
    }

    /** A dictation exercise that already has its audio file. */
    public function withAudio(): static
    {
        return $this->state(fn () => [
            'audio_path' => 'a1/dictation-'.fake()->unique()->numberBetween(1, 999999).'.mp3',
        ]);
    }

    private static function skillFor(ExerciseType $type): Skill
    {
        return match ($type) {
            ExerciseType::Dictation => Skill::Listening,
            ExerciseType::Writing => Skill::Writing,
            ExerciseType::Speaking => Skill::Speaking,
            default => Skill::Grammar,
        };
    }
}
