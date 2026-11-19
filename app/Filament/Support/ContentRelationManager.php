<?php

namespace App\Filament\Support;

use App\Filament\Actions\ArchiveAction;
use App\Filament\Actions\RestoreArchivedAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\Resource;
use Filament\Tables\Columns\Column;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

/**
 * A list of a parent's children, ordered by `position`, with an archived
 * filter, reorder, and edit, archive and restore on each row. It registers no
 * create, delete or bulk action: the panel only retouches existing rows.
 * "Edit" opens the child's own page, so payload editors have room.
 */
abstract class ContentRelationManager extends RelationManager
{
    use ReordersSiblingRows;

    /**
     * The resource whose edit page opens when a row is edited.
     *
     * @return class-string<\Filament\Resources\Resource>
     */
    abstract protected static function childResource(): string;

    /** @return list<Column> */
    abstract protected function columns(): array;

    protected function canReorder(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        $related = $table->getModel();

        return $table
            ->columns([...$this->columns(), ArchivedState::column()])
            ->defaultSort('position')
            ->reorderable('position', $this->canReorder() && Gate::allows('reorder', $related))
            ->filters([ArchivedState::filter()])
            ->recordActions([
                EditAction::make()->url(fn (Model $record): string => static::childResource()::getUrl('edit', ['record' => $record])),
                ArchiveAction::make(),
                RestoreArchivedAction::make(),
            ]);
    }
}
