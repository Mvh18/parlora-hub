<?php

namespace App\Models;

use App\Enums\ExamKind;
use App\Models\Concerns\Archivable;
use App\Models\Concerns\HasStableKey;
use Database\Factories\ExamFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A level exam (one per CEFR level) or the placement exam (no level).
 *
 * @property int $id
 * @property string $key
 * @property ExamKind $kind
 * @property int|null $level_id
 * @property string $title
 * @property int $time_limit_minutes
 * @property int $pass_score Percentage (0-100).
 * @property int|null $max_attempts Null means unlimited.
 * @property int $cooldown_hours
 * @property Carbon|null $archived_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Level|null $level
 */
#[Fillable(['key', 'kind', 'level_id', 'title', 'time_limit_minutes', 'pass_score', 'max_attempts', 'cooldown_hours'])]
class Exam extends Model
{
    /** @use HasFactory<ExamFactory> */
    use Archivable, HasFactory, HasStableKey;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'kind' => ExamKind::class,
            'time_limit_minutes' => 'integer',
            'pass_score' => 'integer',
            'max_attempts' => 'integer',
            'cooldown_hours' => 'integer',
        ];
    }

    /** @return BelongsTo<Level, $this> */
    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    /** @return HasMany<ExamSection, $this> */
    public function sections(): HasMany
    {
        return $this->hasMany(ExamSection::class)->orderBy('position');
    }
}
