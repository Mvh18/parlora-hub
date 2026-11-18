<?php

namespace App\Filament\Support;

use BackedEnum;
use Illuminate\Support\Str;

/**
 * Select options for a backed enum: stored value => readable label.
 */
final class EnumOptions
{
    /**
     * @param  class-string<BackedEnum>  $enum
     * @return array<string, string>
     */
    public static function for(string $enum): array
    {
        $options = [];

        foreach ($enum::cases() as $case) {
            $options[(string) $case->value] = Str::headline($case->name);
        }

        return $options;
    }
}
