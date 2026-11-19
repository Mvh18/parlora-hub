<?php

namespace App\Filament\Support;

use App\Filament\Actions\ArchiveAction;
use App\Filament\Actions\RestoreArchivedAction;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\Resource as FilamentResource;
use Filament\Tables\Columns\Column;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

/**
 * A list of a parent's children with an archived filter, and edit, archive and
 * restore on each row. It registers no create, delete or bulk action: the
 * panel only retouches existing rows. "Edit" opens the child's own page, so
 * payload editors have room.
 *
 * Children ordered by a `position` column can be reordered; a list without one
 * (exam questions) or one that only shows who uses a row (a passage's
 * exercises) overrides `orderColumn()` and, for the latter, `rowActions()`.
 */
abstract class ContentRelationManager extends RelationManager
{
    use ReordersSiblingRows;

    /**
     * The resource whose edit page opens when a row is edited.
     *
     * @return class-string<FilamentResource>
     */
    abstract protected static function childResource(): string;

    /** @return list<Column> */
    abstract protected function columns(): array;

    /** The column that orders the children and that reordering rewrites, or null for none. */
    protected function orderColumn(): ?string
    {
        return 'position';
    }

    /** @return list<Action> */
    protected function rowActions(): array
    {
        return [
            $this->editAction(),
            ArchiveAction::make(),
            RestoreArchivedAction::make(),
        ];
    }

    protected function editAction(): EditAction
    {
        return EditAction::make()->url(fn (Model $record): string => static::childResource()::getUrl('edit', ['record' => $record]));
    }

    public function table(Table $table): Table
    {
        $order = $this->orderColumn();

        return $table
            ->columns([...$this->columns(), ArchivedState::column()])
            ->defaultSort($order ?? 'id')
            ->reorderable($order, $order !== null && Gate::allows('reorder', $table->getModel()))
            ->filters([ArchivedState::filter()])
            ->recordActions($this->rowActions());
    }
}
