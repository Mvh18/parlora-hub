<?php

namespace App\Filament\Resources\Passages\RelationManagers;

use App\Filament\Resources\Exercises\ExerciseResource;
use App\Filament\Support\ContentRelationManager;
use Filament\Tables\Columns\TextColumn;

/**
 * The exercises that use this passage, read-only: each row opens its own page.
 * Their `position` orders them within a lesson, not here.
 */
class ExercisesRelationManager extends ContentRelationManager
{
    protected static string $relationship = 'exercises';

    protected static function childResource(): string
    {
        return ExerciseResource::class;
    }

    protected function orderColumn(): ?string
    {
        return null;
    }

    protected function rowActions(): array
    {
        return [$this->editAction()];
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('key')->searchable(),
            TextColumn::make('lesson.key')->label('Lesson'),
            TextColumn::make('type')->badge(),
            TextColumn::make('prompt')->limit(60)->searchable(),
        ];
    }
}
