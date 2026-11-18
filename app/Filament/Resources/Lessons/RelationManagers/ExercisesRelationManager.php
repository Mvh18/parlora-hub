<?php

namespace App\Filament\Resources\Lessons\RelationManagers;

use App\Filament\Resources\Exercises\ExerciseResource;
use App\Filament\Support\ContentRelationManager;
use Filament\Tables\Columns\TextColumn;

class ExercisesRelationManager extends ContentRelationManager
{
    protected static string $relationship = 'exercises';

    protected static function childResource(): string
    {
        return ExerciseResource::class;
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('position'),
            TextColumn::make('key')->searchable(),
            TextColumn::make('type')->badge(),
            TextColumn::make('skill')->badge(),
            TextColumn::make('prompt')->limit(60)->searchable(),
        ];
    }
}
