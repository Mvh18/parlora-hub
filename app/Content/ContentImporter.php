<?php

namespace App\Content;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Writes a validated content tree to the database in one transaction.
 *
 * Imports are serialised with a transaction-scoped advisory lock, rows are
 * matched by key and written through Eloquent (so the model hooks still guard
 * them), parents before children, and a row that moved keeps its id. Positions
 * are rewritten in three steps so that no state in between breaks
 * `unique(parent, position)`:
 *
 * - A: every stored row goes to `-position` (−1…−999);
 * - B: every row that changes is saved at `-(final + 1000)` (−1001…−1999);
 * - C: per parent, the file rows take `1…n` and the rows that are not in the
 *   files take `n+1…` in their previous order.
 *
 * A raw position update does not touch `updated_at`, so an unchanged row
 * stays exactly as it was.
 */
final class ContentImporter
{
    /** The advisory lock id that every import takes, so two imports never interleave. */
    public const LOCK_ID = 7_242_090_301;

    /** Steps A and B stay apart because no parent has more than this many children (`maxItems` in the schema). */
    private const TEMPORARY_OFFSET = 1000;

    /** @var array<string, array<string, int>> table → key → id */
    private array $ids = [];

    /**
     * Plan the import and, when the plan has no errors, write it. On any
     * failure the whole import rolls back.
     */
    public function import(ContentTree $tree): ImportPlan
    {
        return DB::transaction(function () use ($tree): ImportPlan {
            DB::select('select pg_advisory_xact_lock(?)', [self::LOCK_ID]);

            $plan = ImportPlan::build($tree);

            if ($plan->hasErrors()) {
                return $plan;
            }

            $this->ids = [];

            foreach (ContentTables::all() as $name => $table) {
                $this->writeTable($table, $tree, $plan);
            }

            return $plan;
        });
    }

    private function writeTable(ContentTable $table, ContentTree $tree, ImportPlan $plan): void
    {
        /** @var array<string, Model> $existing */
        $existing = $table->model::query()->get()->keyBy('key')->all();
        $this->ids[$table->name] = array_map(fn (Model $model): int => (int) $model->getKey(), $existing);

        if ($table->positioned) {
            // Step A.
            $this->query($table)->update(['position' => DB::raw('-position')]);
        }

        foreach ($tree->rows($table->name) as $row) {
            if ($plan->action($table->name, $row->key) === ImportPlan::UNCHANGED) {
                continue;
            }

            $model = $existing[$row->key] ?? new $table->model;

            // Step B.
            RowFiller::fill(
                $model,
                $table,
                $row,
                $this->id($table->parentTable, $row->parentKey),
                $this->id(ContentTables::PASSAGES, $row->passageKey),
                $row->position === null ? null : -($row->position + self::TEMPORARY_OFFSET),
            );

            $model->save();

            $this->ids[$table->name][$row->key] = (int) $model->getKey();
        }

        if ($table->positioned) {
            $this->finalPositions($table, $tree);
        }
    }

    /**
     * Step C. File rows take their array position; the other rows of the same
     * parent follow, in the order they had before the import.
     */
    private function finalPositions(ContentTable $table, ContentTree $tree): void
    {
        $column = $table->parentColumn;
        /** @var array<string, int> $last parent id (or '' for the whole table) → highest file position */
        $last = [];

        foreach ($tree->rows($table->name) as $row) {
            $id = $this->ids[$table->name][$row->key];
            $parent = $column === null ? '' : (string) $this->id($table->parentTable, $row->parentKey);

            $this->query($table)->where('id', $id)->update(['position' => $row->position]);
            $last[$parent] = max($last[$parent] ?? 0, (int) $row->position);
        }

        $others = $this->query($table)
            ->whereNotIn('key', $tree->keys($table->name))
            // After step A, `-position` keeps the previous order.
            ->orderByDesc('position')
            ->get(['id', ...($column === null ? [] : [$column])]);

        foreach ($others as $other) {
            $parent = $column === null ? '' : (string) $other->{$column};
            $last[$parent] = ($last[$parent] ?? 0) + 1;

            $this->query($table)->where('id', $other->id)->update(['position' => $last[$parent]]);
        }
    }

    private function id(?string $table, ?string $key): ?int
    {
        return $table === null || $key === null ? null : ($this->ids[$table][$key] ?? null);
    }

    private function query(ContentTable $table): Builder
    {
        return $table->model::query()->toBase();
    }
}
