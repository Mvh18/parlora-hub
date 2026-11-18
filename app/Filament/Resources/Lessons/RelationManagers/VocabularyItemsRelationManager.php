<?php

namespace App\Filament\Resources\Lessons\RelationManagers;

use App\Filament\Resources\VocabularyItems\VocabularyItemResource;
use App\Filament\Support\ContentRelationManager;
use App\Models\VocabularyItem;
use Filament\Tables\Columns\TextColumn;

class VocabularyItemsRelationManager extends ContentRelationManager
{
    protected static string $relationship = 'vocabularyItems';

    protected static function childResource(): string
    {
        return VocabularyItemResource::class;
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('position'),
            TextColumn::make('term')->searchable(),
            TextColumn::make('kind')->badge(),
            TextColumn::make('meaning')
                ->state(fn (VocabularyItem $record): string => $record->getTranslation('meaning', 'en', false)),
        ];
    }
}
