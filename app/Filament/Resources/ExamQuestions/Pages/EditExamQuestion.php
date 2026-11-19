<?php

namespace App\Filament\Resources\ExamQuestions\Pages;

use App\Filament\Resources\ExamQuestions\ExamQuestionResource;
use App\Filament\Resources\ExamSections\ExamSectionResource;
use App\Filament\Support\EditContentRecord;
use App\Models\ExamQuestion;

class EditExamQuestion extends EditContentRecord
{
    protected static string $resource = ExamQuestionResource::class;

    protected function parentUrl(): ?string
    {
        /** @var ExamQuestion $question */
        $question = $this->getRecord();

        return ExamSectionResource::getUrl('edit', ['record' => $question->exam_section_id]);
    }
}
