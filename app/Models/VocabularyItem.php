<?php

namespace App\Models;

use App\Enums\VocabularyKind;
use App\Models\Concerns\Archivable;
use App\Models\Concerns\HasContentTranslations;
use App\Models\Concerns\HasStableKey;
use Database\Factories\VocabularyItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A word, phrasal verb or expression taught in a lesson.
 *
 * @property int $id
 * @property string $key
 * @property int $lesson_id
 * @property string $term
 * @property VocabularyKind $kind
 * @property string $meaning Translatable (read it with getTranslation()).
 * @property string|null $example
 * @property int $position
 * @property Carbon|null $archived_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Lesson $lesson
 */
#[Fillable(['key', 'lesson_id', 'term', 'kind', 'meaning', 'example', 'position'])]
class VocabularyItem extends Model
{
    /** @use HasFactory<VocabularyItemFactory> */
    use Archivable, HasContentTranslations, HasFactory, HasStableKey;

    /** @var list<string> */
    public array $translatable = ['meaning'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'kind' => VocabularyKind::class,
            'position' => 'integer',
        ];
    }

    /** @return BelongsTo<Lesson, $this> */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }
}
