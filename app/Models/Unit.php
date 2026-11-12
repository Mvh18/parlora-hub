<?php

namespace App\Models;

use App\Models\Concerns\Archivable;
use App\Models\Concerns\HasStableKey;
use Database\Factories\UnitFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A unit (one week of study) inside a level.
 *
 * @property int $id
 * @property string $key
 * @property int $level_id
 * @property string $title
 * @property int $position
 * @property Carbon|null $archived_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Level $level
 */
#[Fillable(['key', 'level_id', 'title', 'position'])]
class Unit extends Model
{
    /** @use HasFactory<UnitFactory> */
    use Archivable, HasFactory, HasStableKey;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['position' => 'integer'];
    }

    /** @return BelongsTo<Level, $this> */
    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    /** @return HasMany<Lesson, $this> */
    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class)->orderBy('position');
    }
}
