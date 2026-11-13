<?php

namespace App\Enums;

use App\Data\Exercises\DictationContent;
use App\Data\Exercises\DictationSolution;
use App\Data\Exercises\FillGapContent;
use App\Data\Exercises\FillGapSolution;
use App\Data\Exercises\MatchingContent;
use App\Data\Exercises\MatchingSolution;
use App\Data\Exercises\MultipleChoiceContent;
use App\Data\Exercises\MultipleChoiceSolution;
use App\Data\Exercises\ReorderContent;
use App\Data\Exercises\ReorderSolution;
use App\Data\Exercises\SpeakingContent;
use App\Data\Exercises\SpeakingSolution;
use App\Data\Exercises\TransformationContent;
use App\Data\Exercises\TransformationSolution;
use App\Data\Exercises\ValidatesAgainstContent;
use App\Data\Exercises\WritingContent;
use App\Data\Exercises\WritingSolution;
use App\Enums\Concerns\HasValues;
use Spatie\LaravelData\Data;

/**
 * The exercise catalogue shared by lesson exercises and exam questions.
 *
 * Each case maps to one content class (shown to the learner) and one
 * solution class (server only) under `App\Data\Exercises`.
 */
enum ExerciseType: string
{
    use HasValues;

    case MultipleChoice = 'multiple_choice';
    case FillGap = 'fill_gap';
    case Reorder = 'reorder';
    case Matching = 'matching';
    case Transformation = 'transformation';
    case Dictation = 'dictation';
    case Writing = 'writing';
    case Speaking = 'speaking';

    /** @return class-string<Data> */
    public function contentClass(): string
    {
        return match ($this) {
            self::MultipleChoice => MultipleChoiceContent::class,
            self::FillGap => FillGapContent::class,
            self::Reorder => ReorderContent::class,
            self::Matching => MatchingContent::class,
            self::Transformation => TransformationContent::class,
            self::Dictation => DictationContent::class,
            self::Writing => WritingContent::class,
            self::Speaking => SpeakingContent::class,
        };
    }

    /** @return class-string<Data&ValidatesAgainstContent> */
    public function solutionClass(): string
    {
        return match ($this) {
            self::MultipleChoice => MultipleChoiceSolution::class,
            self::FillGap => FillGapSolution::class,
            self::Reorder => ReorderSolution::class,
            self::Matching => MatchingSolution::class,
            self::Transformation => TransformationSolution::class,
            self::Dictation => DictationSolution::class,
            self::Writing => WritingSolution::class,
            self::Speaking => SpeakingSolution::class,
        };
    }

    /** Whether Phase 3 should skip the exercise until it has an audio file. */
    public function requiresAudio(): bool
    {
        return $this === self::Dictation;
    }
}
