<?php

namespace App\Data\Exercises;

use Spatie\LaravelData\Data;

final class ReorderContent extends Data
{
    /**
     * @param  list<array{id: string, text: string}>  $tokens  stored shuffled
     */
    public function __construct(
        public array $tokens,
    ) {}

    /** @return array<string, mixed> */
    public static function rules(): array
    {
        return ExerciseIds::itemRules('tokens', 2, 30);
    }
}
