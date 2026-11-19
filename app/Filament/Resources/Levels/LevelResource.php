<?php

namespace App\Filament\Resources\Levels;

use App\Enums\CefrLevel;
use App\Filament\Actions\ArchiveAction;
use App\Filament\Actions\RestoreArchivedAction;
use App\Filament\Resources\Levels\Pages\EditLevel;
use App\Filament\Resources\Levels\Pages\ListLevels;
use App\Filament\Resources\Levels\RelationManagers\UnitsRelationManager;
use App\Filament\Support\ArchivedState;
use App\Filament\Support\EnumOptions;
use App\Filament\Support\FormFields;
use App\Filament\Support\ReimportNotice;
use App\Models\Level;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * The CEFR levels, the top of the syllabus. Levels keep their CEFR order and
 * code: only the title and the pass threshold are retouched here.
 */
class LevelResource extends Resource
{
    protected static ?string $model = Level::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            ReimportNotice::make(),
            FormFields::key(),
            Select::make('code')->options(EnumOptions::for(CefrLevel::class))->disabled()->dehydrated(false),
            TextInput::make('title')->required()->maxLength(255),
            TextInput::make('pass_threshold')->numeric()->integer()->minValue(0)->maxValue(100)->required()->suffix('%'),
            TextInput::make('position')->disabled()->dehydrated(false),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('position')->sortable(),
                TextColumn::make('key')->searchable(),
                TextColumn::make('title')->searchable(),
                TextColumn::make('pass_threshold')->suffix('%'),
                ArchivedState::column(),
            ])
            ->defaultSort('position')
            ->filters([ArchivedState::filter()])
            ->recordActions([
                EditAction::make(),
                ArchiveAction::make(),
                RestoreArchivedAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [UnitsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLevels::route('/'),
            'edit' => EditLevel::route('/{record}/edit'),
        ];
    }
}
