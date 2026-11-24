<?php

namespace App\Content;

use Illuminate\Database\Eloquent\Model;

/**
 * One content table as the importer and the exporter see it.
 */
final readonly class ContentTable
{
    /**
     * @param  class-string<Model>  $model
     * @param  string|null  $parentTable  the table of the parent row, null for roots
     * @param  string|null  $parentColumn  the foreign key to the parent
     * @param  string|null  $parentRelation  the belongsTo relation that names the parent
     * @param  string|null  $childrenMember  the member of the parent's file fragment that lists these rows
     * @param  bool  $positioned  whether the table has a `position` column
     * @param  bool  $hasPassage  whether rows may reference a passage by key
     * @param  list<string>  $members  the row's own members in a content file, in file order
     *                                 (besides `key`, `passage` and the child arrays)
     */
    public function __construct(
        public string $name,
        public string $model,
        public ?string $parentTable,
        public ?string $parentColumn,
        public ?string $parentRelation,
        public ?string $childrenMember,
        public bool $positioned,
        public bool $hasPassage = false,
        public array $members = [],
    ) {}

    /**
     * The tables whose rows nest in this table's file fragment, in file order.
     * A level's units live in their own files, so a level has none.
     *
     * @return list<ContentTable>
     */
    public function children(): array
    {
        if ($this->name === ContentTables::LEVELS) {
            return [];
        }

        return array_values(array_filter(
            ContentTables::all(),
            fn (ContentTable $table): bool => $table->parentTable === $this->name && $table->childrenMember !== null,
        ));
    }

    /** Whether positions are unique across the whole table instead of per parent. */
    public function hasGlobalPositions(): bool
    {
        return $this->positioned && $this->parentColumn === null;
    }
}
