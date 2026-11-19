<?php

namespace App\Filament\Resources\ExamSections\Pages;

use App\Filament\Resources\Exams\ExamResource;
use App\Filament\Resources\ExamSections\ExamSectionResource;
use App\Filament\Support\EditContentRecord;
use App\Models\ExamSection;

class EditExamSection extends EditContentRecord
{
    protected static string $resource = ExamSectionResource::class;

    protected function parentUrl(): ?string
    {
        /** @var ExamSection $section */
        $section = $this->getRecord();

        return ExamResource::getUrl('edit', ['record' => $section->exam_id]);
    }
}
