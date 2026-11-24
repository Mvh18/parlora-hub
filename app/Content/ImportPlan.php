<?php

namespace App\Content;

use App\Models\Exam;
use App\Models\Level;
use Illuminate\Database\Eloquent\Model;

/**
 * What an import would do, table by table: which keys it creates, updates,
 * leaves unchanged, archives and restores. "Unchanged" means the row already
 * holds exactly what the files say, including its parent and position, so the
 * importer does not write it and its `updated_at` stays put.
 *
 * Conflicts that only the database can reveal (an exam key that changed for a
 * level, a level code taken by another key) are errors here, before anything
 * is written.
 */
final class ImportPlan
{
    public const CREATE = 'create';

    public const UPDATE = 'update';

    public const UNCHANGED = 'unchanged';

    public const ARCHIVE = 'archive';

    public const RESTORE = 'restore';

    /** The plan actions in the order the summary prints them. */
    public const ACTIONS = [self::CREATE, self::UPDATE, self::UNCHANGED, self::ARCHIVE, self::RESTORE];

    /** @var array<string, array<string, list<string>>> table → action → keys */
    private array $keys = [];

    /** @var array<string, array<string, string>> table → key → action */
    private array $actions = [];

    /** @var list<ContentIssue> */
    private array $errors = [];

    private function __construct()
    {
        foreach (ContentTables::order() as $table) {
            $this->keys[$table] = array_fill_keys(self::ACTIONS, []);
            $this->actions[$table] = [];
        }
    }

    /**
     * Compare the tree with the database. Reads only.
     */
    public static function build(ContentTree $tree): self
    {
        $plan = new self;
        /** @var array<string, array<string, int>> $ids table → key → id of the rows that exist */
        $ids = [];

        foreach (ContentTables::all() as $name => $table) {
            /** @var array<string, Model> $existing */
            $existing = $table->model::query()->get()->keyBy('key')->all();
            $ids[$name] = array_map(fn (Model $model): int => (int) $model->getKey(), $existing);

            foreach ($tree->rows($name) as $row) {
                $model = $existing[$row->key] ?? null;

                $plan->add($name, $row->key, match (true) {
                    $model === null => self::CREATE,
                    (bool) $model->getAttribute('archived_at') => self::RESTORE,
                    self::changes($model, $table, $row, $ids) => self::UPDATE,
                    default => self::UNCHANGED,
                });
            }

            $inFiles = array_flip($tree->keys($name));

            foreach ($existing as $key => $model) {
                if (! isset($inFiles[$key]) && $model->getAttribute('archived_at') === null) {
                    $plan->add($name, (string) $key, self::ARCHIVE);
                }
            }
        }

        $plan->levelConflicts($tree);
        $plan->examConflicts($tree, $ids);

        return $plan;
    }

    /**
     * The keys of a table that get an action.
     *
     * @return list<string>
     */
    public function keys(string $table, string $action): array
    {
        return $this->keys[$table][$action] ?? [];
    }

    /** The action planned for a key, or null when the key is not in the plan. */
    public function action(string $table, string $key): ?string
    {
        return $this->actions[$table][$key] ?? null;
    }

    /** How many keys of a table get an action. */
    public function count(string $table, string $action): int
    {
        return count($this->keys($table, $action));
    }

    /** @return list<ContentIssue> */
    public function errors(): array
    {
        return $this->errors;
    }

    public function hasErrors(): bool
    {
        return $this->errors !== [];
    }

    /** Whether the import would write anything at all. */
    public function changesAnything(): bool
    {
        foreach ($this->keys as $actions) {
            foreach ($actions as $action => $keys) {
                if ($action !== self::UNCHANGED && $keys !== []) {
                    return true;
                }
            }
        }

        return false;
    }

    private function add(string $table, string $key, string $action): void
    {
        $this->keys[$table][$action][] = $key;
        $this->actions[$table][$key] = $action;
    }

    /**
     * Whether writing the row would change the stored one. A parent or passage
     * that does not exist yet means the row is moving to a new one.
     *
     * @param  array<string, array<string, int>>  $ids
     */
    private static function changes(Model $model, ContentTable $table, ContentRow $row, array $ids): bool
    {
        $parentId = null;

        if ($table->parentTable !== null && $row->parentKey !== null) {
            $parentId = $ids[$table->parentTable][$row->parentKey] ?? null;

            if ($parentId === null) {
                return true;
            }
        }

        $passageId = null;

        if ($row->passageKey !== null) {
            $passageId = $ids[ContentTables::PASSAGES][$row->passageKey] ?? null;

            if ($passageId === null) {
                return true;
            }
        }

        $candidate = clone $model;
        RowFiller::fill($candidate, $table, $row, $parentId, $passageId, $row->position);

        return RowFiller::hasChanges($candidate);
    }

    /**
     * `levels.code` is unique, archived rows included, and keys never change:
     * a level file must keep the key the database already has for its code.
     */
    private function levelConflicts(ContentTree $tree): void
    {
        $byCode = Level::query()->pluck('key', 'code')->all();

        foreach ($tree->rows(ContentTables::LEVELS) as $row) {
            $other = $byCode[(string) $row->attributes['code']] ?? null;

            if ($other !== null && $other !== $row->key) {
                $this->errors[] = new ContentIssue($row->file, '/key', "The level {$row->attributes['code']} already exists with the key `{$other}`, and keys never change: use `{$other}` instead of `{$row->key}`.");
            }
        }
    }

    /**
     * `exams.level_id` is unique, archived rows included, and keys never
     * change: a level exam file must keep the key of the exam its level
     * already has.
     *
     * @param  array<string, array<string, int>>  $ids
     */
    private function examConflicts(ContentTree $tree, array $ids): void
    {
        $byLevel = Exam::query()->whereNotNull('level_id')->pluck('key', 'level_id')->all();

        foreach ($tree->rows(ContentTables::EXAMS) as $row) {
            $levelId = $row->parentKey === null ? null : ($ids[ContentTables::LEVELS][$row->parentKey] ?? null);
            $other = $levelId === null ? null : ($byLevel[$levelId] ?? null);

            if ($other !== null && $other !== $row->key) {
                $this->errors[] = new ContentIssue($row->file, '/key', "The level `{$row->parentKey}` already has the exam `{$other}`, and exam keys never change: use `{$other}` instead of `{$row->key}`.");
            }
        }
    }
}
