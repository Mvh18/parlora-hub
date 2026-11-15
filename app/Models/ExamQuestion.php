<?php

namespace App\Models;

use App\Casts\ExercisePayloadCast;
use App\Enums\ExerciseType;
use App\Models\Concerns\Archivable;
use App\Models\Concerns\HasContentTranslations;
use App\Models\Concerns\HasStableKey;
use App\Models\Concerns\ValidatesExercisePayloads;
use Database\Factories\ExamQuestionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\LaravelData\Data;

/**
 * A question in an exam section's bank. It shares the exercise catalogue:
 * the same types, payload classes and validation.
 *
 * @property int $id
 * @property string $key
 * @property int $exam_section_id
 * @property int|null $passage_id
 * @property ExerciseType $type
 * @property Data $content Typed by `type`.
 * @property Data $solution Typed by `type`; hidden from serialisation.
 * @property string|null $explanation Translatable (read it with getTranslation()).
 * @property int $points
 * @property Carbon|null $archived_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ExamSection $section
 * @property-read Passage|null $passage
 */
#[Fillable(['key', 'exam_section_id', 'passage_id', 'type', 'content', 'solution', 'explanation', 'points'])]
#[Hidden(['solution'])]
class ExamQuestion extends Model
{
    /** @use HasFactory<ExamQuestionFactory> */
    use Archivable, HasContentTranslations, HasFactory, HasStableKey, ValidatesExercisePayloads;

    /** @var list<string> */
    public array $translatable = ['explanation'];

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'type' => ExerciseType::class,
            'content' => ExercisePayloadCast::class.':'.ExercisePayloadCast::CONTENT,
            'solution' => ExercisePayloadCast::class.':'.ExercisePayloadCast::SOLUTION,
            'points' => 'integer',
        ];
    }

    /** @return list<string> */
    protected function optionalTranslations(): array
    {
        return ['explanation'];
    }

    /** @return BelongsTo<ExamSection, $this> */
    public function section(): BelongsTo
    {
        return $this->belongsTo(ExamSection::class, 'exam_section_id');
    }

    /** @return BelongsTo<Passage, $this> */
    public function passage(): BelongsTo
    {
        return $this->belongsTo(Passage::class);
    }
}
