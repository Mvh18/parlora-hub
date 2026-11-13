<?php

use App\Data\Exercises\ExercisePayloads;
use App\Data\Exercises\FillGapContent;
use App\Data\Exercises\MultipleChoiceSolution;
use App\Enums\ExerciseType;
use App\Exceptions\InvalidExercisePayload;
use Database\Factories\Support\ExerciseSamples;
use Illuminate\Validation\ValidationException;

test('validate returns the typed payloads', function () {
    $payloads = ExercisePayloads::validate(
        ExerciseType::MultipleChoice,
        ExerciseSamples::content(ExerciseType::MultipleChoice),
        ExerciseSamples::solution(ExerciseType::MultipleChoice),
    );

    expect($payloads['solution'])->toBeInstanceOf(MultipleChoiceSolution::class)
        ->and($payloads['solution']->correct)->toBe(['b']);
});

test('it throws a validation exception naming every failing path', function () {
    $content = ExerciseSamples::content(ExerciseType::FillGap);
    $content['text'] = 'No gaps.';

    try {
        ExercisePayloads::validate(ExerciseType::FillGap, $content, ['answers' => []]);
        $this->fail('Expected an exception.');
    } catch (InvalidExercisePayload $exception) {
        expect($exception)->toBeInstanceOf(ValidationException::class)
            ->and(array_keys($exception->errors()))->toContain('content.text', 'solution.answers');
    }
});

test('structural and cross-field errors are reported separately', function () {
    // Structural errors stop the run before cross-checks, so they never mix.
    expect(payloadErrors(ExerciseType::MultipleChoice, ExerciseSamples::content(ExerciseType::MultipleChoice), ['correct' => []]))
        ->toBe(['solution.correct']);
});

test('validate without saving reports an empty accepted list', function () {
    expect(fn () => ExercisePayloads::validate(
        ExerciseType::Transformation,
        ExerciseSamples::content(ExerciseType::Transformation),
        ['accepted' => []],
    ))->toThrow(InvalidExercisePayload::class);

    expect(payloadErrors(ExerciseType::Transformation, ExerciseSamples::content(ExerciseType::Transformation), ['accepted' => []]))
        ->toBe(['solution.accepted']);
});

test('a payload of the wrong type is rejected', function () {
    expect(payloadErrors(ExerciseType::Matching, ExerciseSamples::content(ExerciseType::MultipleChoice), ExerciseSamples::solution(ExerciseType::MultipleChoice)))
        ->not->toBe([]);
});

test('a type change needs a matching payload', function () {
    expect(payloadErrors(ExerciseType::Matching, ExerciseSamples::content(ExerciseType::Reorder), ExerciseSamples::solution(ExerciseType::Reorder)))
        ->not->toBe([]);
});

test('typed content round-trips through arrays', function () {
    $content = ExerciseSamples::content(ExerciseType::FillGap);

    expect(FillGapContent::from($content)->toArray())->toBe($content);
});

test('serialised payloads hold no php class names', function () {
    foreach (ExerciseType::cases() as $type) {
        $payloads = ExercisePayloads::validate($type, ExerciseSamples::content($type), ExerciseSamples::solution($type));

        expect(json_encode($payloads['content']->toArray()).json_encode($payloads['solution']->toArray()))
            ->not->toContain('App\\\\');
    }
});
