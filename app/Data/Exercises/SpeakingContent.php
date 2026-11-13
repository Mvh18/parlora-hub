<?php

namespace App\Data\Exercises;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;

final class SpeakingContent extends Data
{
    /**
     * @param  list<RubricCriterion>  $rubric
     */
    public function __construct(
        public string $task,
        public int $prep_seconds,
        public int $max_seconds,
        #[DataCollectionOf(RubricCriterion::class)]
        public array $rubric,
    ) {}

    /** @return array<string, mixed> */
    public static function rules(): array
    {
        return [
            'task' => ['required', 'string', 'max:2000'],
            'prep_seconds' => ['required', 'integer', 'min:0', 'max:600'],
            'max_seconds' => ['required', 'integer', 'min:5', 'max:900'],
            'rubric' => ['required', 'array', 'min:1'],
            'rubric.*.key' => ['distinct'],
        ];
    }

    public function rubricTotal(): int
    {
        return RubricCriterion::total($this->rubric);
    }
}
