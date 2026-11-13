<?php

namespace App\Data\Exercises;

use App\Enums\ExerciseType;
use App\Exceptions\InvalidExercisePayload;
use Illuminate\Validation\ValidationException;
use Spatie\LaravelData\Data;

/**
 * The single entry point for checking an exercise payload.
 *
 * Models call it on save; the importer and the panel call it directly so they
 * can report errors before writing anything.
 */
final class ExercisePayloads
{
    /**
     * Validate `content` and `solution` against the classes of `$type`, then
     * run the cross-checks between them.
     *
     * @param  array<string, mixed>  $content
     * @param  array<string, mixed>  $solution
     * @return array{content: Data, solution: Data&ValidatesAgainstContent}
     *
     * @throws InvalidExercisePayload naming every failing path (`content.*`, `solution.*`)
     */
    public static function validate(ExerciseType $type, array $content, array $solution): array
    {
        $contentClass = $type->contentClass();
        $solutionClass = $type->solutionClass();

        $errors = [
            ...self::structuralErrors('content', $contentClass, $content),
            ...self::structuralErrors('solution', $solutionClass, $solution),
        ];

        InvalidExercisePayload::throwIfAny($errors);

        $typedContent = $contentClass::from($content);
        $typedSolution = $solutionClass::from($solution);
        $typedSolution->assertConsistentWith($typedContent);

        return ['content' => $typedContent, 'solution' => $typedSolution];
    }

    /**
     * @param  class-string<Data>  $class
     * @param  array<string, mixed>  $payload
     * @return array<string, string>
     */
    private static function structuralErrors(string $side, string $class, array $payload): array
    {
        try {
            $class::validate($payload);
        } catch (ValidationException $exception) {
            $errors = [];

            foreach ($exception->errors() as $path => $messages) {
                $errors["{$side}.{$path}"] = $messages[0];
            }

            return $errors;
        }

        return [];
    }
}
