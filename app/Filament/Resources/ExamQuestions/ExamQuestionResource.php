<?php

namespace App\Filament\Resources\ExamQuestions;

use App\Enums\ExerciseType;
use App\Filament\Resources\ExamQuestions\Pages\EditExamQuestion;
use App\Filament\Resources\Exams\ExamResource;
use App\Filament\Support\EnumOptions;
use App\Filament\Support\FormFields;
use App\Filament\Support\PayloadFields;
use App\Filament\Support\ReachedFromParent;
use App\Filament\Support\ReimportNotice;
use App\Filament\Support\TranslatableFields;
use App\Models\ExamQuestion;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;

/** A question of a section's bank. Reached from its section (or passage), not from the navigation. */
class ExamQuestionResource extends Resource
{
    use ReachedFromParent;

    protected static ?string $model = ExamQuestion::class;

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
            FormFields::parent('exam_section_id', 'section', searchColumns: ['key']),
            FormFields::parent('passage_id', 'passage', required: false),
            Select::make('type')->options(EnumOptions::for(ExerciseType::class))->required()->live(),
            TextInput::make('points')->numeric()->integer()->minValue(0)->maxValue(65535)->required(),
            TranslatableFields::make('explanation')->columnSpanFull(),
            ...array_map(fn ($editor) => $editor->columnSpanFull(), PayloadFields::make()),
        ])->columns(2);
    }

    public static function getPages(): array
    {
        return ['edit' => EditExamQuestion::route('/{record}/edit')];
    }
}
