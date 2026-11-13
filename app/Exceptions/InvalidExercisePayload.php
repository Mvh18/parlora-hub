<?php

namespace App\Exceptions;

use Illuminate\Validation\ValidationException;

/**
 * An exercise or exam question payload that breaks its type's contract.
 *
 * The messages are keyed by the failing path (`content.options.1.text`,
 * `solution.correct.0`) so the panel and the importer can point at the field.
 */
class InvalidExercisePayload extends ValidationException
{
    /**
     * Throw when any error was collected.
     *
     * @param  array<string, string>  $errors  message by failing path
     *
     * @throws self
     */
    public static function throwIfAny(array $errors): void
    {
        if ($errors === []) {
            return;
        }

        throw self::withMessages(array_map(fn (string $message): array => [$message], $errors));
    }
}
