<?php

namespace App\Data\Exercises;

use Spatie\LaravelData\Data;

final class MatchingContent extends Data
{
    /**
     * @param  list<array{id: string, text: string}>  $left
     * @param  list<array{id: string, text: string}>  $right  may hold more items than `left` (distractors)
     */
    public function __construct(
        public array $left,
        public array $right,
    ) {}

    /** @return array<string, mixed> */
    public static function rules(): array
    {
        return [
            ...ExerciseIds::itemRules('left', 2, 12),
            ...ExerciseIds::itemRules('right', 2, 12),
        ];
    }
}
