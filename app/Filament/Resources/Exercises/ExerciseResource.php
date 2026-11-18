<?php

namespace App\Filament\Resources\Exercises;

use App\Enums\ExerciseType;
use App\Enums\Skill;
use App\Filament\Resources\Exercises\Pages\EditExercise;
use App\Filament\Support\EnumOptions;
use App\Filament\Support\FormFields;
use App\Filament\Support\PayloadFields;
use App\Filament\Support\ReachedFromParent;
use App\Filament\Support\ReimportNotice;
use App\Filament\Support\TranslatableFields;
use App\Models\Exercise;
use App\Rules\AudioPath;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;

/** An exercise. Reached from its lesson (or passage), not from the navigation. */
class ExerciseResource extends Resource
{
    use ReachedFromParent;

    protected static ?string $model = Exercise::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $recordTitleAttribute = 'key';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            ReimportNotice::make(),
            FormFields::key(),
            FormFields::parent('lesson_id', 'lesson'),
            FormFields::parent('passage_id', 'passage', required: false),
            Select::make('type')->options(EnumOptions::for(ExerciseType::class))->required()->live(),
            Select::make('skill')->options(EnumOptions::for(Skill::class))->required(),
            FormFields::position('exercises', 'lesson_id'),
            TextInput::make('audio_path')->rule(new AudioPath)->maxLength(255),
            Textarea::make('prompt')->required()->rows(2)->columnSpanFull(),
            TranslatableFields::make('explanation')->columnSpanFull(),
            ...array_map(fn ($editor) => $editor->columnSpanFull(), PayloadFields::make()),
        ])->columns(2);
    }

    public static function getPages(): array
    {
        return ['edit' => EditExercise::route('/{record}/edit')];
    }
}
