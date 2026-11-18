<?php

namespace App\Filament\Resources\Exams;

use App\Enums\ExamKind;
use App\Filament\Actions\ArchiveAction;
use App\Filament\Actions\RestoreArchivedAction;
use App\Filament\Resources\Exams\Pages\EditExam;
use App\Filament\Resources\Exams\Pages\ListExams;
use App\Filament\Resources\Exams\RelationManagers\SectionsRelationManager;
use App\Filament\Support\ArchivedState;
use App\Filament\Support\EnumOptions;
use App\Filament\Support\FormFields;
use App\Filament\Support\ReimportNotice;
use App\Models\Exam;
use BackedEnum;
use Closure;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Level exams (one per level) and the placement exam. The two rules the
 * database enforces, one exam per level and one active placement exam, are
 * checked on the form first so a clash is a field error and not an exception.
 */
class ExamResource extends Resource
{
    protected static ?string $model = Exam::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        $isPlacement = fn (Get $get): bool => $get('kind') === ExamKind::Placement->value;

        return $schema->components([
            ReimportNotice::make(),
            FormFields::key(),
            Select::make('kind')
                ->options(EnumOptions::for(ExamKind::class))
                ->required()
                ->live()
                ->rule(fn (?Model $record): Closure => self::placementIsFree($record)),
            Select::make('level_id')
                ->relationship('level', 'key')
                ->getOptionLabelFromRecordUsing(fn (Model $record): string => implode(' - ', array_filter([$record->getAttribute('key'), $record->getAttribute('title')])))
                ->required(fn (Get $get): bool => ! $isPlacement($get))
                ->hidden($isPlacement)
                // A placement exam has no level (a database check), so a hidden select still stores null.
                ->dehydratedWhenHidden()
                ->dehydrateStateUsing(fn (Get $get, mixed $state): mixed => $isPlacement($get) ? null : $state)
                ->unique(table: 'exams', column: 'level_id', ignoreRecord: true),
            TextInput::make('title')->required()->maxLength(255),
            TextInput::make('time_limit_minutes')->numeric()->integer()->minValue(1)->maxValue(65535)->required()->suffix('min'),
            TextInput::make('pass_score')->numeric()->integer()->minValue(0)->maxValue(100)->required()->suffix('%'),
            TextInput::make('max_attempts')->numeric()->integer()->minValue(1)->maxValue(65535)->helperText('Leave empty for unlimited attempts.'),
            TextInput::make('cooldown_hours')->numeric()->integer()->minValue(0)->maxValue(65535)->required()->suffix('h'),
        ])->columns(2);
    }

    /**
     * At most one active placement exam exists. An archived exam does not
     * hold the slot (the database index only covers active rows).
     *
     * @return Closure(string, mixed, Closure): void
     */
    private static function placementIsFree(?Model $record): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($record): void {
            if ($value !== ExamKind::Placement->value || ($record instanceof Exam && $record->isArchived())) {
                return;
            }

            $taken = Exam::query()
                ->active()
                ->where('kind', ExamKind::Placement)
                ->when($record, fn ($query) => $query->whereKeyNot($record?->getKey()))
                ->exists();

            if ($taken) {
                $fail(__('admin.exams.placement_taken'));
            }
        };
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('key')->searchable(),
                TextColumn::make('title')->searchable(),
                TextColumn::make('kind')->badge(),
                TextColumn::make('level.key')->label('Level')->placeholder('-'),
                TextColumn::make('pass_score')->suffix('%'),
                ArchivedState::column(),
            ])
            ->defaultSort('key')
            ->filters([ArchivedState::filter()])
            ->recordActions([
                EditAction::make(),
                ArchiveAction::make(),
                RestoreArchivedAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [SectionsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListExams::route('/'),
            'edit' => EditExam::route('/{record}/edit'),
        ];
    }
}
