<?php

namespace App\Filament\Resources\Exercises\Pages;

use App\Filament\Resources\Exercises\ExerciseResource;
use App\Filament\Resources\Lessons\LessonResource;
use App\Filament\Support\EditContentRecord;
use App\Models\Exercise;

class EditExercise extends EditContentRecord
{
    protected static string $resource = ExerciseResource::class;

    protected function parentUrl(): ?string
    {
        /** @var Exercise $exercise */
        $exercise = $this->getRecord();

        return LessonResource::getUrl('edit', ['record' => $exercise->lesson_id]);
    }
}
