<?php

use App\Enums\ExerciseType;
use Database\Factories\Support\ExerciseSamples;

function openErrors(ExerciseType $type, array $content = [], array $solution = []): array
{
    return payloadErrors(
        $type,
        [...ExerciseSamples::content($type), ...$content],
        [...ExerciseSamples::solution($type), ...$solution],
    );
}

describe('transformation', function () {
    test('a valid exercise passes', function () {
        expect(openErrors(ExerciseType::Transformation))->toBe([]);
    });

    test('needs at least one accepted rewrite', function () {
        expect(openErrors(ExerciseType::Transformation, solution: ['accepted' => []]))->toContain('solution.accepted');
    });

    test('needs a sentence, a keyword and a positive word limit', function () {
        expect(openErrors(ExerciseType::Transformation, ['sentence' => '', 'keyword' => '', 'max_words' => 0]))
            ->toContain('content.sentence', 'content.keyword', 'content.max_words');
    });
});

describe('dictation', function () {
    test('a valid exercise passes without any audio', function () {
        expect(openErrors(ExerciseType::Dictation))->toBe([]);
    });

    test('plays are unlimited when null and positive otherwise', function () {
        expect(openErrors(ExerciseType::Dictation, ['max_plays' => null]))->toBe([])
            ->and(openErrors(ExerciseType::Dictation, ['max_plays' => 0]))->toContain('content.max_plays');
    });

    test('needs a transcript', function () {
        expect(openErrors(ExerciseType::Dictation, solution: ['transcript' => '']))->toContain('solution.transcript');
    });

    test('accepted spellings are optional', function () {
        expect(payloadErrors(ExerciseType::Dictation, [], ['transcript' => 'Hello there.']))->toBe([]);
    });
});

describe('writing', function () {
    test('a valid exercise passes', function () {
        expect(openErrors(ExerciseType::Writing))->toBe([]);
    });

    test('the minimum cannot exceed the maximum', function () {
        expect(openErrors(ExerciseType::Writing, ['min_words' => 120, 'max_words' => 80]))->toBe(['content.min_words']);
    });

    test('the model answer is optional', function () {
        expect(payloadErrors(ExerciseType::Writing, ExerciseSamples::content(ExerciseType::Writing), []))->toBe([]);
    });

    test('needs a rubric', function () {
        expect(openErrors(ExerciseType::Writing, ['rubric' => []]))->toContain('content.rubric');
    });
});

describe('speaking', function () {
    test('a valid exercise passes', function () {
        expect(openErrors(ExerciseType::Speaking))->toBe([]);
    });

    test('has sensible time limits', function () {
        expect(openErrors(ExerciseType::Speaking, ['prep_seconds' => -1, 'max_seconds' => 0]))
            ->toContain('content.prep_seconds', 'content.max_seconds');
    });

    test('needs a rubric', function () {
        expect(openErrors(ExerciseType::Speaking, ['rubric' => []]))->toContain('content.rubric');
    });
});
