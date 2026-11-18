<?php

namespace App\Filament\Resources\Passages;

use App\Filament\Actions\ArchiveAction;
use App\Filament\Actions\RestoreArchivedAction;
use App\Filament\Resources\Passages\Pages\EditPassage;
use App\Filament\Resources\Passages\Pages\ListPassages;
use App\Filament\Resources\Passages\RelationManagers\ExamQuestionsRelationManager;
use App\Filament\Resources\Passages\RelationManagers\ExercisesRelationManager;
use App\Filament\Support\ArchivedState;
use App\Filament\Support\FormFields;
use App\Filament\Support\ReimportNotice;
use App\Models\Passage;
use App\Rules\AudioPath;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Reading texts and listening material shared by exercises and exam questions.
 * The transcript is hidden from serialisation (it gives the listening answers
 * away), so the form loads it from the record explicitly.
 */
class PassageResource extends Resource
{
    protected static ?string $model = Passage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            ReimportNotice::make(),
            FormFields::key(),
            TextInput::make('title')->required()->maxLength(255),
            TextInput::make('audio_path')->rule(new AudioPath)->maxLength(255),
            MarkdownEditor::make('body')->columnSpanFull(),
            Textarea::make('transcript')
                ->rows(6)
                ->afterStateHydrated(fn (Textarea $component, ?Model $record) => $component->state($record?->getAttribute('transcript')))
                ->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('key')->searchable(),
                TextColumn::make('title')->searchable(),
                TextColumn::make('audio_path')->placeholder('-'),
                TextColumn::make('exercises_count')->counts('exercises'),
                TextColumn::make('exam_questions_count')->counts('examQuestions'),
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
        return [
            ExercisesRelationManager::class,
            ExamQuestionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPassages::route('/'),
            'edit' => EditPassage::route('/{record}/edit'),
        ];
    }
}
