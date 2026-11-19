<?php

namespace App\Filament\Resources\ExamSections\RelationManagers;

use App\Filament\Resources\ExamQuestions\ExamQuestionResource;
use App\Filament\Support\ContentRelationManager;
use Filament\Tables\Columns\TextColumn;

/** A section's question bank. Questions have no position: the attempt draws a random subset. */
class QuestionsRelationManager extends ContentRelationManager
{
    protected static string $relationship = 'questions';

    protected static function childResource(): string
    {
        return ExamQuestionResource::class;
    }

    protected function orderColumn(): ?string
    {
        return null;
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('key')->searchable(),
            TextColumn::make('type')->badge(),
            TextColumn::make('points'),
            TextColumn::make('passage.key')->label('Passage')->placeholder('-'),
        ];
    }
}
