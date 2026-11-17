<?php

namespace App\Filament\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Rewrites the positions of a parent's children (units, lessons, vocabulary
 * items, exercises, exam sections) without breaking `unique(parent, position)`.
 *
 * Writing the new positions one row at a time collides with the unique index
 * as soon as two rows swap. In one transaction this first moves every sibling
 * to a disjoint temporary range (the negated position, which cannot overflow
 * the `smallint` column), then writes the final positions 1…n: the given ids
 * first, in the given order, then any siblings left out (archived rows the
 * list was not showing) in their current order.
 *
 * The caller authorizes the `reorder` ability.
 */
final class ReorderSiblings
{
    /**
     * @template TChild of Model
     * @template TParent of Model
     *
     * @param  HasMany<TChild, TParent>  $siblings
     * @param  list<int|string>  $orderedIds
     *
     * @throws InvalidArgumentException when an id is not a child of this parent
     */
    public static function run(HasMany $siblings, array $orderedIds): void
    {
        $model = $siblings->getRelated();
        $keyName = $model->getKeyName();

        // A fresh builder per statement: the relation's own builder would
        // accumulate every where() below.
        $query = fn () => $siblings->getQuery()->clone()->toBase();

        DB::transaction(function () use ($query, $orderedIds, $keyName): void {
            $current = $query()
                ->orderBy('position')
                ->lockForUpdate()
                ->pluck($keyName)
                ->map(fn (mixed $id): string => (string) $id)
                ->all();

            $given = array_values(array_unique(array_map(strval(...), $orderedIds)));
            $unknown = array_diff($given, $current);

            if ($unknown !== []) {
                throw new InvalidArgumentException(sprintf('Not a sibling: %s.', implode(', ', $unknown)));
            }

            $final = [...$given, ...array_values(array_diff($current, $given))];

            $query()->update(['position' => DB::raw('-position')]);

            foreach ($final as $index => $id) {
                $query()
                    ->where($keyName, $id)
                    ->update(['position' => $index + 1, 'updated_at' => now()]);
            }
        });
    }
}
