<?php

namespace App\Filament\Resources\VocabularyItems\Pages;

use App\Filament\Resources\Lessons\LessonResource;
use App\Filament\Resources\VocabularyItems\VocabularyItemResource;
use App\Filament\Support\EditContentRecord;
use App\Models\VocabularyItem;

class EditVocabularyItem extends EditContentRecord
{
    protected static string $resource = VocabularyItemResource::class;

    protected function parentUrl(): ?string
    {
        /** @var VocabularyItem $item */
        $item = $this->getRecord();

        return LessonResource::getUrl('edit', ['record' => $item->lesson_id]);
    }
}
