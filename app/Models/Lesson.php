<?php

namespace App\Models;

use App\Enums\LessonType;
use App\Models\Concerns\Archivable;
use App\Models\Concerns\HasContentTranslations;
use App\Models\Concerns\HasStableKey;
use Database\Factories\LessonFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A lesson of grammar, vocabulary, phrasal verbs or one of the four skills.
 *
 * @property int $id
 * @property string $key
 * @property int $unit_id
 * @property LessonType $type
 * @property string $title
 * @property string|null $theory Markdown, translatable (read it with getTranslation()).
 * @property int $position
 * @property Carbon|null $archived_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Unit $unit
 */
#[Fillable(['key', 'unit_id', 'type', 'title', 'theory', 'position'])]
class Lesson extends Model
{
    /** @use HasFactory<LessonFactory> */
    use Archivable, HasContentTranslations, HasFactory, HasStableKey;

    /** @var list<string> */
    public array $translatable = ['theory'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => LessonType::class,
            'position' => 'integer',
        ];
    }

    /** @return list<string> */
    protected function optionalTranslations(): array
    {
        return ['theory'];
    }

    /** @return BelongsTo<Unit, $this> */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /** @return HasMany<VocabularyItem, $this> */
    public function vocabularyItems(): HasMany
    {
        return $this->hasMany(VocabularyItem::class)->orderBy('position');
    }

    /** @return HasMany<Exercise, $this> */
    public function exercises(): HasMany
    {
        return $this->hasMany(Exercise::class)->orderBy('position');
    }
}
