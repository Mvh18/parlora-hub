<?php

namespace App\Data\Exercises;

use Spatie\LaravelData\Data;

final class TransformationContent extends Data
{
    public function __construct(
        public string $sentence,
        public string $keyword,
        public int $max_words,
    ) {}

    /** @return array<string, mixed> */
    public static function rules(): array
    {
        return [
            'sentence' => ['required', 'string', 'max:500'],
            'keyword' => ['required', 'string', 'max:60'],
            'max_words' => ['required', 'integer', 'min:1', 'max:20'],
        ];
    }
}
