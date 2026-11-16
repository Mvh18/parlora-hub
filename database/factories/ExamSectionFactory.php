<?php

namespace Database\Factories;

use App\Enums\ExamSkill;
use App\Enums\GradingMode;
use App\Models\Exam;
use App\Models\ExamSection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExamSection>
 */
class ExamSectionFactory extends Factory
{
    /**
     * Define the model's default state: an auto-graded use-of-English section.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => 'exam-section-'.fake()->unique()->numberBetween(1, 999999),
            'exam_id' => Exam::factory(),
            'skill' => ExamSkill::UseOfEnglish,
            'points' => 20,
            'grading_mode' => GradingMode::Auto,
            'questions_per_attempt' => null,
            'position' => fake()->unique()->numberBetween(1, 30000),
        ];
    }

    /** A section marked by Parlora Tutor, such as writing or speaking. */
    public function ai(ExamSkill $skill = ExamSkill::Writing): static
    {
        return $this->state(fn () => ['skill' => $skill, 'grading_mode' => GradingMode::Ai]);
    }
}
