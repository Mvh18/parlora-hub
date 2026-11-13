<?php

namespace App\Data\Exercises;

use App\Exceptions\InvalidExercisePayload;
use InvalidArgumentException;
use Spatie\LaravelData\Data;

final class FillGapSolution extends Data implements ValidatesAgainstContent
{
    /**
     * @param  array<string, list<string>>  $answers  accepted answers by gap id
     */
    public function __construct(
        public array $answers,
    ) {}

    /** @return array<string, mixed> */
    public static function rules(): array
    {
        return [
            'answers' => ['required', 'array', 'min:1'],
            'answers.*' => ['required', 'array', 'min:1'],
            'answers.*.*' => ['required', 'string', 'max:200'],
        ];
    }

    public function assertConsistentWith(Data $content): void
    {
        if (! $content instanceof FillGapContent) {
            throw new InvalidArgumentException('Expected FillGapContent.');
        }

        $gaps = $content->gapIds();
        $errors = [];

        foreach ($gaps as $id) {
            if (! array_key_exists($id, $this->answers)) {
                $errors["solution.answers.{$id}"] = trans('validation.content.payload.gap_without_answers', ['id' => $id]);
            }
        }

        foreach (array_keys($this->answers) as $id) {
            if (! in_array((string) $id, $gaps, true)) {
                $errors["solution.answers.{$id}"] = trans('validation.content.payload.answers_without_gap', ['id' => $id]);
            }
        }

        InvalidExercisePayload::throwIfAny($errors);
    }
}
