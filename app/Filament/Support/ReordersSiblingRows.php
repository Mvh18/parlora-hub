<?php

namespace App\Filament\Support;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Gate;

/**
 * For relation managers: the drag-and-drop reorder goes through
 * ReorderSiblings (one transaction, no unique collisions) instead of
 * Filament's row-by-row update, and it authorizes `reorder` first because
 * `reorderTable()` is a public Livewire method any request can call.
 */
trait ReordersSiblingRows
{
    /**
     * @param  list<int|string>  $order
     */
    public function reorderTable(array $order, int|string|null $draggedRecordKey = null): void
    {
        $relationship = $this->getRelationship();

        Gate::authorize('reorder', $this->getTable()->getModel());

        if ($relationship instanceof HasMany) {
            ReorderSiblings::run($relationship, $order);
        }
    }
}
