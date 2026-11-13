<?php

namespace App\Data\Exercises;

use App\Exceptions\InvalidExercisePayload;
use InvalidArgumentException;
use Spatie\LaravelData\Data;

final class MatchingSolution extends Data implements ValidatesAgainstContent
{
    /**
     * @param  list<array{0: string, 1: string}>  $pairs  `[leftId, rightId]`
     */
    public function __construct(
        public array $pairs,
    ) {}

    /** @return array<string, mixed> */
    public static function rules(): array
    {
        return [
            'pairs' => ['required', 'array', 'min:1'],
            'pairs.*' => ['required', 'array', 'size:2'],
            'pairs.*.0' => ['required', 'string'],
            'pairs.*.1' => ['required', 'string'],
        ];
    }

    public function assertConsistentWith(Data $content): void
    {
        if (! $content instanceof MatchingContent) {
            throw new InvalidArgumentException('Expected MatchingContent.');
        }

        $left = ExerciseIds::of($content->left);
        $right = ExerciseIds::of($content->right);
        $paired = [];
        $errors = [];

        foreach ($this->pairs as $index => $pair) {
            if (! in_array($pair[0], $left, true) || ! in_array($pair[1], $right, true)) {
                $errors["solution.pairs.{$index}"] = trans('validation.content.payload.pair_unknown_id');

                continue;
            }

            if (in_array($pair[0], $paired, true)) {
                $errors["solution.pairs.{$index}"] = trans('validation.content.payload.pair_left_repeated', ['id' => $pair[0]]);

                continue;
            }

            $paired[] = $pair[0];
        }

        if ($errors === []) {
            foreach (array_diff($left, $paired) as $id) {
                $errors['solution.pairs'] = trans('validation.content.payload.left_unpaired', ['id' => $id]);
            }
        }

        InvalidExercisePayload::throwIfAny($errors);
    }
}
