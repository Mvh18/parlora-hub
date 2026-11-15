<?php

namespace Database\Factories;

use App\Enums\ExerciseType;
use App\Models\ExamQuestion;
use App\Models\ExamSection;
use Database\Factories\Support\ExerciseSamples;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExamQuestion>
 */
class ExamQuestionFactory extends Factory
{
    /**
     * Define the model's default state: a valid multiple-choice question.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => 'exam-question-'.fake()->unique()->numberBetween(1, 999999),
            'exam_section_id' => ExamSection::factory(),
            'passage_id' => null,
            'type' => ExerciseType::MultipleChoice,
            'content' => ExerciseSamples::content(ExerciseType::MultipleChoice),
            'solution' => ExerciseSamples::solution(ExerciseType::MultipleChoice),
            'points' => 1,
        ];
    }

    /** A valid question of the given type. */
    public function ofType(ExerciseType $type): static
    {
        return $this->state(fn () => [
            'type' => $type,
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
}
