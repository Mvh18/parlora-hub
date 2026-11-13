<?php

namespace App\Data\Exercises;

use App\Rules\TranslatedText;
use Spatie\LaravelData\Data;

/**
 * One scoring criterion of a writing or speaking rubric.
 *
 * Criteria carry their own `max_points`, so the scale shared with Parlora
 * Tutor can be decided later without changing the shape.
 */
final class RubricCriterion extends Data
{
    /**
     * @param  array<string, string>  $description  Per-locale text; English required.
     */
    public function __construct(
        public string $key,
        public array $description,
        public int $max_points,
    ) {}

    /** @return array<string, mixed> */
    public static function rules(): array
    {
        return [
            'key' => ['required', 'string', 'max:60', 'regex:'.ExerciseIds::PATTERN],
            'description' => ['required', 'array', new TranslatedText],
            'max_points' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * The total maximum of a rubric.
     *
     * @param  iterable<RubricCriterion>  $rubric
     */
    public static function total(iterable $rubric): int
    {
        $total = 0;

        foreach ($rubric as $criterion) {
            $total += $criterion->max_points;
        }

        return $total;
    }
}
