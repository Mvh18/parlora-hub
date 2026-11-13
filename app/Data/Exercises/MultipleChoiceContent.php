<?php

namespace App\Data\Exercises;

use Spatie\LaravelData\Data;

final class MultipleChoiceContent extends Data
{
    /**
     * @param  list<array{id: string, text: string}>  $options
     */
    public function __construct(
        public array $options,
        public bool $multiple = false,
    ) {}

    /** @return array<string, mixed> */
    public static function rules(): array
    {
        return [
            ...ExerciseIds::itemRules('options', 2, 8),
            'multiple' => ['boolean'],
        ];
    }
}
