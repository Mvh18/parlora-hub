<?php

namespace App\Filament\Resources\Lessons\Pages;

use App\Filament\Resources\Lessons\LessonResource;
use App\Filament\Resources\Units\UnitResource;
use App\Filament\Support\EditContentRecord;
use App\Models\Lesson;

class EditLesson extends EditContentRecord
{
    protected static string $resource = LessonResource::class;

    protected function parentUrl(): ?string
    {
        /** @var Lesson $lesson */
        $lesson = $this->getRecord();

        return UnitResource::getUrl('edit', ['record' => $lesson->unit_id]);
    }
}
