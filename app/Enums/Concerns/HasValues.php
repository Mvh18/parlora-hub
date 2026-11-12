<?php

namespace App\Enums\Concerns;

/**
 * The backing values of a string enum, for database check constraints and
 * validation rules.
 */
trait HasValues
{
    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $case): string => $case->value, self::cases());
    }
}
