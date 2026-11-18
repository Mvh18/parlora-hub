<?php

namespace App\Filament\Resources\Lessons;

use App\Enums\LessonType;
use App\Filament\Resources\Lessons\Pages\EditLesson;
use App\Filament\Resources\Lessons\RelationManagers\ExercisesRelationManager;
use App\Filament\Resources\Lessons\RelationManagers\VocabularyItemsRelationManager;
use App\Filament\Support\EnumOptions;
use App\Filament\Support\FormFields;
use App\Filament\Support\ReachedFromParent;
use App\Filament\Support\ReimportNotice;
use App\Filament\Support\TranslatableFields;
use App\Models\Lesson;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;

/** A lesson. Reached from its unit, not from the navigation. */
class LessonResource extends Resource
{
    use ReachedFromParent;

    protected static ?string $model = Lesson::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            ReimportNotice::make(),
            FormFields::key(),
            FormFields::parent('unit_id', 'unit'),
            Select::make('type')->options(EnumOptions::for(LessonType::class))->required(),
            TextInput::make('title')->required()->maxLength(255),
            FormFields::position('lessons', 'unit_id'),
            TranslatableFields::make('theory', markdown: true)->columnSpanFull(),
        ])->columns(2);
    }

    public static function getRelations(): array
    {
        return [
            VocabularyItemsRelationManager::class,
            ExercisesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return ['edit' => EditLesson::route('/{record}/edit')];
    }
}
