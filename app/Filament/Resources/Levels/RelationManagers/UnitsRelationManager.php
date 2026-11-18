<?php

namespace App\Filament\Resources\Levels\RelationManagers;

use App\Filament\Resources\Units\UnitResource;
use App\Filament\Support\ContentRelationManager;
use Filament\Tables\Columns\TextColumn;

class UnitsRelationManager extends ContentRelationManager
{
    protected static string $relationship = 'units';

    protected static function childResource(): string
    {
        return UnitResource::class;
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('position'),
            TextColumn::make('key')->searchable(),
            TextColumn::make('title')->searchable(),
        ];
    }
}
