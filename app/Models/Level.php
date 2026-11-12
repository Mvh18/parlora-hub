<?php

namespace App\Models;

use App\Enums\CefrLevel;
use App\Models\Concerns\Archivable;
use App\Models\Concerns\HasStableKey;
use Database\Factories\LevelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * A CEFR level of the course, A1 to C1.
 *
 * @property int $id
 * @property string $key
 * @property CefrLevel $code
 * @property string $title
 * @property int $position
 * @property int $pass_threshold Percentage (0-100) a learner needs to pass the level.
 * @property Carbon|null $archived_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['key', 'code', 'title', 'position', 'pass_threshold'])]
class Level extends Model
{
    /** @use HasFactory<LevelFactory> */
    use Archivable, HasFactory, HasStableKey;

    protected static function booted(): void
    {
        static::saving(function (Level $level): void {
            $threshold = $level->pass_threshold;

            if ($threshold < 0 || $threshold > 100) {
                throw ValidationException::withMessages([
                    'pass_threshold' => [trans('validation.between.numeric', [
                        'attribute' => 'pass threshold',
                        'min' => 0,
                        'max' => 100,
                    ])],
                ]);
            }
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'code' => CefrLevel::class,
            'position' => 'integer',
            'pass_threshold' => 'integer',
        ];
    }

    /** @return HasMany<Unit, $this> */
    public function units(): HasMany
    {
        return $this->hasMany(Unit::class)->orderBy('position');
    }
}
