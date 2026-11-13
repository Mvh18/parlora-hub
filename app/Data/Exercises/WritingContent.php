<?php

namespace App\Data\Exercises;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;

final class WritingContent extends Data
{
    /**
     * @param  list<RubricCriterion>  $rubric
     */
    public function __construct(
        public string $task,
        public int $min_words,
        public int $max_words,
        #[DataCollectionOf(RubricCriterion::class)]
        public array $rubric,
    ) {}

    /** @return array<string, mixed> */
    public static function rules(): array
    {
        return [
            'task' => ['required', 'string', 'max:2000'],
            // `lte` lives on min_words so the error names min_words, not max_words.
            'min_words' => ['required', 'integer', 'min:1', 'lte:max_words'],
            'max_words' => ['required', 'integer', 'min:1', 'max:2000'],
            'rubric' => ['required', 'array', 'min:1'],
            'rubric.*.key' => ['distinct'],
        ];
    }

    /** @return array<string, string> */
    public static function messages(): array
    {
        return [
            'min_words.lte' => trans('validation.content.payload.word_limits'),
        ];
    }

    public function rubricTotal(): int
    {
        return RubricCriterion::total($this->rubric);
    }
}
