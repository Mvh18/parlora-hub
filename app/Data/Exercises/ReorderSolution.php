<?php

namespace App\Data\Exercises;

use App\Exceptions\InvalidExercisePayload;
use InvalidArgumentException;
use Spatie\LaravelData\Data;

final class ReorderSolution extends Data implements ValidatesAgainstContent
{
    /**
     * @param  list<list<string>>  $orders  accepted orders of token ids
     */
    public function __construct(
        public array $orders,
    ) {}

    /** @return array<string, mixed> */
    public static function rules(): array
    {
        return [
            'orders' => ['required', 'array', 'min:1'],
            'orders.*' => ['required', 'array', 'min:2'],
            'orders.*.*' => ['required', 'string'],
        ];
    }

    public function assertConsistentWith(Data $content): void
    {
        if (! $content instanceof ReorderContent) {
            throw new InvalidArgumentException('Expected ReorderContent.');
        }

        $ids = ExerciseIds::of($content->tokens);
        $sorted = $ids;
        sort($sorted);
        $errors = [];

        foreach ($this->orders as $index => $order) {
            $check = $order;
            sort($check);

            if ($check !== $sorted) {
                $errors["solution.orders.{$index}"] = trans('validation.content.payload.order_not_permutation');

                continue;
            }

            if ($order === $ids) {
                $errors['content.tokens'] = trans('validation.content.payload.tokens_reveal_order');
            }
        }

        InvalidExercisePayload::throwIfAny($errors);
    }
}
