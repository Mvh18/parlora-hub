<?php

namespace App\Filament\Support;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Validation\Rules\Unique;

/**
 * Form fields every content edit form repeats.
 */
final class FormFields
{
    /** The stable key: shown, never editable, never sent to the model. */
    public static function key(): TextInput
    {
        return TextInput::make('key')->disabled()->dehydrated(false);
    }

    /**
     * A select for the row's parent (or passage), listed by key and title.
     * Changing it moves the row; the position rule then checks the new parent.
     */
    public static function parent(string $column, string $relationship, bool $required = true): Select
    {
        return Select::make($column)
            ->relationship($relationship, 'key')
            ->getOptionLabelFromRecordUsing(fn ($record): string => trim("{$record->key} - {$record->title}"))
            ->searchable(['key', 'title'])
            ->preload()
            ->required($required);
    }

    /**
     * The position among siblings, unique within the selected parent, so a
     * collision after a move is a form error and not a database error.
     */
    public static function position(string $table, string $parentColumn): TextInput
    {
        return TextInput::make('position')
            ->numeric()
            ->integer()
            ->minValue(1)
            ->maxValue(32767)
            ->required()
            ->unique(
                table: $table,
                column: 'position',
                ignoreRecord: true,
                modifyRuleUsing: fn (Unique $rule, Get $get): Unique => $rule->where($parentColumn, $get($parentColumn)),
            );
    }
}
