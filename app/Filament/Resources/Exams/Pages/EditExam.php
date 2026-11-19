<?php

namespace App\Filament\Resources\Exams\Pages;

use App\Filament\Resources\Exams\ExamResource;
use App\Filament\Support\EditContentRecord;

class EditExam extends EditContentRecord
{
    protected static string $resource = ExamResource::class;

    protected function parentUrl(): ?string
    {
        return ExamResource::getUrl('index');
    }
}
