<?php

namespace App\Filament\Resources\Units;

use App\Filament\Resources\Units\Pages\EditUnit;
use App\Filament\Resources\Units\RelationManagers\LessonsRelationManager;
use App\Filament\Support\FormFields;
use App\Filament\Support\ReachedFromParent;
use App\Filament\Support\ReimportNotice;
use App\Models\Unit;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;

/** A unit (one week of study). Reached from its level, not from the navigation. */
class UnitResource extends Resource
{
    use ReachedFromParent;

    protected static ?string $model = Unit::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            ReimportNotice::make(),
            FormFields::key(),
            FormFields::parent('level_id', 'level'),
            TextInput::make('title')->required()->maxLength(255),
            FormFields::position('units', 'level_id'),
        ])->columns(2);
    }

    public static function getRelations(): array
    {
        return [LessonsRelationManager::class];
    }

    public static function getPages(): array
    {
        return ['edit' => EditUnit::route('/{record}/edit')];
    }
}
