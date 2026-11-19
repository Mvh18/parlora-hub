<?php

namespace App\Filament\Support;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * The archived state in every content list: a badge on archived rows and a
 * filter that shows active rows by default, archived rows, or all of them.
 */
final class ArchivedState
{
    public static function column(): TextColumn
    {
        return TextColumn::make('archived_at')
            ->label(__('admin.archived.column'))
            ->badge()
            ->color('gray')
            ->state(fn (Model $record) => method_exists($record, 'isArchived') && $record->isArchived()
                ? __('admin.archived.badge')
                : null);
    }

    public static function filter(): TernaryFilter
    {
        return TernaryFilter::make('archived')
            ->label(__('admin.archived.filter'))
            ->placeholder(__('admin.archived.all'))
            ->trueLabel(__('admin.archived.only'))
            ->falseLabel(__('admin.archived.active'))
            ->queries(
                true: fn (Builder $query): Builder => $query->whereNotNull($query->qualifyColumn('archived_at')),
                false: fn (Builder $query): Builder => $query->whereNull($query->qualifyColumn('archived_at')),
                blank: fn (Builder $query): Builder => $query,
            )
            ->default(false);
    }
}
