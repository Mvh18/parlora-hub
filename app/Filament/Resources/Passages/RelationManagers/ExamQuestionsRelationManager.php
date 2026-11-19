<?php

namespace App\Filament\Resources\Passages\RelationManagers;

use App\Filament\Resources\ExamQuestions\ExamQuestionResource;
use App\Filament\Support\ContentRelationManager;
use Filament\Tables\Columns\TextColumn;

/** The exam questions that use this passage, read-only: each row opens its own page. */
class ExamQuestionsRelationManager extends ContentRelationManager
{
    protected static string $relationship = 'examQuestions';

    protected static function childResource(): string
    {
        return ExamQuestionResource::class;
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
            TextColumn::make('section.key')->label('Section'),
            TextColumn::make('type')->badge(),
            TextColumn::make('points'),
        ];
    }
}
