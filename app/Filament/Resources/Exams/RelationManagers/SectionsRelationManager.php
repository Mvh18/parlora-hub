<?php

namespace App\Filament\Resources\Exams\RelationManagers;

use App\Filament\Resources\ExamSections\ExamSectionResource;
use App\Filament\Support\ContentRelationManager;
use Filament\Tables\Columns\TextColumn;

class SectionsRelationManager extends ContentRelationManager
{
    protected static string $relationship = 'sections';

    protected static function childResource(): string
    {
        return ExamSectionResource::class;
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('position'),
            TextColumn::make('key')->searchable(),
            TextColumn::make('skill')->badge(),
            TextColumn::make('grading_mode')->badge(),
            TextColumn::make('points'),
            TextColumn::make('questions_per_attempt')->placeholder('All'),
        ];
    }
}
