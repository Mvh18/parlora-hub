<?php

namespace App\Filament\Resources\ExamSections;

use App\Enums\ExamSkill;
use App\Enums\GradingMode;
use App\Filament\Resources\Exams\ExamResource;
use App\Filament\Resources\ExamSections\Pages\EditExamSection;
use App\Filament\Support\EnumOptions;
use App\Filament\Support\FormFields;
use App\Filament\Support\ReachedFromParent;
use App\Filament\Support\ReimportNotice;
use App\Models\ExamSection;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;

/** An exam section and its question bank. Reached from its exam, not from the navigation. */
class ExamSectionResource extends Resource
{
    use ReachedFromParent;

    protected static ?string $model = ExamSection::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $recordTitleAttribute = 'key';

    protected static function indexResource(): string
    {
        return ExamResource::class;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            ReimportNotice::make(),
            FormFields::key(),
            FormFields::parent('exam_id', 'exam'),
            Select::make('skill')->options(EnumOptions::for(ExamSkill::class))->required(),
            Select::make('grading_mode')->options(EnumOptions::for(GradingMode::class))->required(),
            TextInput::make('points')->numeric()->integer()->minValue(0)->maxValue(65535)->required(),
            TextInput::make('questions_per_attempt')
                ->numeric()
                ->integer()
                ->minValue(1)
                ->maxValue(65535)
                ->helperText('Size of the random subset drawn per attempt. Leave empty to use every question.'),
            FormFields::position('exam_sections', 'exam_id'),
        ])->columns(2);
    }

    public static function getPages(): array
    {
        return ['edit' => EditExamSection::route('/{record}/edit')];
    }
}
