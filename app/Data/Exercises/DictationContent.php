<?php

namespace App\Data\Exercises;

use Spatie\LaravelData\Data;

final class DictationContent extends Data
{
    /**
     * @param  int|null  $max_plays  null = unlimited plays
     */
    public function __construct(
        public ?int $max_plays = null,
    ) {}

    /** @return array<string, mixed> */
    public static function rules(): array
    {
        return [
            'max_plays' => ['nullable', 'integer', 'min:1', 'max:20'],
        ];
    }
}
