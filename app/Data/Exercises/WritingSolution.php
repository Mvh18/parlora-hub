<?php

namespace App\Data\Exercises;

use Spatie\LaravelData\Data;

final class WritingSolution extends Data implements ValidatesAgainstContent
{
    public function __construct(
        public ?string $model_answer = null,
    ) {}

    /** @return array<string, mixed> */
    public static function rules(): array
    {
        return [
            'model_answer' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function assertConsistentWith(Data $content): void
    {
        // No cross-field rules: the model answer is a reference for the learner afterwards.
    }
}
