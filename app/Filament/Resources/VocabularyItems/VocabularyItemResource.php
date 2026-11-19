<?php

namespace App\Filament\Resources\VocabularyItems;

use App\Enums\VocabularyKind;
use App\Filament\Resources\VocabularyItems\Pages\EditVocabularyItem;
use App\Filament\Support\EnumOptions;
use App\Filament\Support\FormFields;
use App\Filament\Support\ReachedFromParent;
use App\Filament\Support\ReimportNotice;
use App\Filament\Support\TranslatableFields;
use App\Models\VocabularyItem;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;

/** A vocabulary item. Reached from its lesson, not from the navigation. */
class VocabularyItemResource extends Resource
{
    use ReachedFromParent;

    protected static ?string $model = VocabularyItem::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $recordTitleAttribute = 'term';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            ReimportNotice::make(),
            FormFields::key(),
            FormFields::parent('lesson_id', 'lesson'),
            TextInput::make('term')->required()->maxLength(255),
            Select::make('kind')->options(EnumOptions::for(VocabularyKind::class))->required(),
            FormFields::position('vocabulary_items', 'lesson_id'),
            Textarea::make('example')->rows(2)->columnSpanFull(),
            TranslatableFields::make('meaning', required: true)->columnSpanFull(),
        ])->columns(2);
    }

    public static function getPages(): array
    {
        return ['edit' => EditVocabularyItem::route('/{record}/edit')];
    }
}
