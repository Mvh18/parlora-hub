<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Content is archived, never deleted: attempts, progress and mistakes keep
 * pointing at rows that left the syllabus.
 *
 * Deliberately no global scope. History must still resolve archived rows, so
 * code that serves learners filters with `active()` explicitly instead of
 * remembering a `withTrashed()` everywhere else.
 *
 * Expects a nullable `archived_at` timestamp column.
 *
 * @mixin Model
 */
trait Archivable
{
    public function initializeArchivable(): void
    {
        $this->mergeCasts(['archived_at' => 'datetime']);
    }

    public function isArchived(): bool
    {
        return $this->getAttribute('archived_at') !== null;
    }

    /** Archiving an archived row keeps its original timestamp. */
    public function archive(): void
    {
        if ($this->isArchived()) {
            return;
        }

        $this->forceFill(['archived_at' => $this->freshTimestamp()])->save();
    }

    public function restore(): void
    {
        $this->forceFill(['archived_at' => null])->save();
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull($this->qualifyColumn('archived_at'));
    }
}
