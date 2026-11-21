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
    ) {}

    /** Whether positions are unique across the whole table instead of per parent. */
    public function hasGlobalPositions(): bool
    {
        return $this->positioned && $this->parentColumn === null;
    }
}
