<?php

namespace App\Data\Exercises;

/**
 * Shared rules for the short author-chosen ids inside payloads (option,
 * token, gap and criterion ids).
 */
final class ExerciseIds
{
    /** Letters, digits, `_` and `-`: safe in JSON paths and `{{gap}}` markers. */
    public const PATTERN = '/^[A-Za-z0-9][A-Za-z0-9_-]*$/';

    /** Matches `{{gap-id}}` markers in fill-gap text. */
    public const GAP_MARKER = '/\{\{\s*([A-Za-z0-9][A-Za-z0-9_-]*)\s*\}\}/';

    /**
     * Rules for a list of `{id, text}` items.
     *
     * @return array<string, list<string>>
     */
    public static function itemRules(string $field, int $min, ?int $max = null): array
    {
        $size = "min:{$min}".($max === null ? '' : "|max:{$max}");

        return [
            $field => ['required', 'array', ...explode('|', $size)],
            "{$field}.*" => ['array'],
            "{$field}.*.id" => ['required', 'string', 'max:40', 'regex:'.self::PATTERN, 'distinct'],
            "{$field}.*.text" => ['required', 'string', 'max:500'],
        ];
    }

    /**
     * The ids of a list of `{id, text}` items.
     *
     * @param  array<int, array{id: string, text: string}>  $items
     * @return list<string>
     */
    public static function of(array $items): array
    {
        return array_values(array_map(fn (array $item): string => $item['id'], $items));
    }
}
