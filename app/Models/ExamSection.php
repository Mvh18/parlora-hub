<?php

namespace App\Models;

use App\Enums\ExamSkill;
use App\Enums\GradingMode;
use App\Models\Concerns\Archivable;
use App\Models\Concerns\HasStableKey;
use Database\Factories\ExamSectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A part of an exam that owns a bank of questions.
 *
 * @property int $id
 * @property string $key
 * @property int $exam_id
 * @property ExamSkill $skill
 * @property int $points
 * @property GradingMode $grading_mode
 * @property int|null $questions_per_attempt Size of the random subset per attempt; null means all.
 * @property int $position
 * @property Carbon|null $archived_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Exam $exam
 */
#[Fillable(['key', 'exam_id', 'skill', 'points', 'grading_mode', 'questions_per_attempt', 'position'])]
class ExamSection extends Model
{
    /** @use HasFactory<ExamSectionFactory> */
    use Archivable, HasFactory, HasStableKey;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'skill' => ExamSkill::class,
            'grading_mode' => GradingMode::class,
            'points' => 'integer',
            'questions_per_attempt' => 'integer',
            'position' => 'integer',
        ];
    }

    /** @return BelongsTo<Exam, $this> */
    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    /** @return HasMany<ExamQuestion, $this> */
    public function questions(): HasMany
    {
        return $this->hasMany(ExamQuestion::class)->orderBy('id');
    }
}
