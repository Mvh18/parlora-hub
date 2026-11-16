<?php

namespace App\Models;

use App\Casts\ExercisePayloadCast;
use App\Enums\ExerciseType;
use App\Enums\Skill;
use App\Models\Concerns\Archivable;
use App\Models\Concerns\HasContentTranslations;
use App\Models\Concerns\HasStableKey;
use App\Models\Concerns\ValidatesExercisePayloads;
use App\Rules\AudioPath;
use Database\Factories\ExerciseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\LaravelData\Data;

/**
 * A practice exercise of a lesson. `content` is what the learner sees,
 * `solution` stays on the server.
 *
 * @property int $id
 * @property string $key
 * @property int $lesson_id
 * @property int|null $passage_id
 * @property ExerciseType $type
 * @property Skill $skill
 * @property string $prompt
 * @property Data $content Typed by `type`.
 * @property Data $solution Typed by `type`; hidden from serialisation.
 * @property string|null $explanation Translatable (read it with getTranslation()).
 * @property string|null $audio_path Path on the private `content-audio` disk.
 * @property int $position
 * @property Carbon|null $archived_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Lesson $lesson
 * @property-read Passage|null $passage
 */
#[Fillable(['key', 'lesson_id', 'passage_id', 'type', 'skill', 'prompt', 'content', 'solution', 'explanation', 'audio_path', 'position'])]
#[Hidden(['solution'])]
class Exercise extends Model
{
    /** @use HasFactory<ExerciseFactory> */
    use Archivable, HasContentTranslations, HasFactory, HasStableKey, ValidatesExercisePayloads;

    /** @var list<string> */
    public array $translatable = ['explanation'];

    protected static function booted(): void
    {
        static::saving(function (self $exercise): void {
            AudioPath::assert('audio_path', $exercise->audio_path);
        });
    }

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'type' => ExerciseType::class,
            'skill' => Skill::class,
            'content' => ExercisePayloadCast::class.':'.ExercisePayloadCast::CONTENT,
            'solution' => ExercisePayloadCast::class.':'.ExercisePayloadCast::SOLUTION,
            'position' => 'integer',
        ];
    }

    /** @return list<string> */
    protected function optionalTranslations(): array
    {
        return ['explanation'];
    }

    /** @return BelongsTo<Lesson, $this> */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    /** @return BelongsTo<Passage, $this> */
    public function passage(): BelongsTo
    {
        return $this->belongsTo(Passage::class);
    }
}
