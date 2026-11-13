<?php

namespace App\Data\Exercises;

use App\Exceptions\InvalidExercisePayload;
use InvalidArgumentException;
use Spatie\LaravelData\Data;

final class MultipleChoiceSolution extends Data implements ValidatesAgainstContent
{
    /**
     * @param  list<string>  $correct  ids of the correct options
     */
    public function __construct(
        public array $correct,
    ) {}

    /** @return array<string, mixed> */
    public static function rules(): array
    {
        return [
            'correct' => ['required', 'array', 'min:1'],
            'correct.*' => ['required', 'string', 'distinct'],
        ];
    }

    public function assertConsistentWith(Data $content): void
    {
        if (! $content instanceof MultipleChoiceContent) {
            throw new InvalidArgumentException('Expected MultipleChoiceContent.');
        }

        $ids = ExerciseIds::of($content->options);
        $errors = [];

        foreach ($this->correct as $index => $id) {
            if (! in_array($id, $ids, true)) {
                $errors["solution.correct.{$index}"] = trans('validation.content.payload.answer_unknown', ['id' => $id]);
            }
        }

        if (! $content->multiple && count($this->correct) > 1) {
            $errors['solution.correct'] = trans('validation.content.payload.single_answer');
        }

        InvalidExercisePayload::throwIfAny($errors);
    }
}
