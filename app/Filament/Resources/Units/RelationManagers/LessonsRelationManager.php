<?php

namespace App\Filament\Resources\Units\RelationManagers;

use App\Filament\Resources\Lessons\LessonResource;
use App\Filament\Support\ContentRelationManager;
use Filament\Tables\Columns\TextColumn;

class LessonsRelationManager extends ContentRelationManager
{
    protected static string $relationship = 'lessons';

    protected static function childResource(): string
    {
        return LessonResource::class;
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('position'),
            TextColumn::make('key')->searchable(),
            TextColumn::make('title')->searchable(),
            TextColumn::make('type')->badge(),
        ];
    }
}
