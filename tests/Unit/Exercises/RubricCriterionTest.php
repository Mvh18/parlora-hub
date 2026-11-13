<?php

use App\Data\Exercises\RubricCriterion;
use App\Data\Exercises\SpeakingContent;
use App\Data\Exercises\WritingContent;
use App\Enums\ExerciseType;
use Database\Factories\Support\ExerciseSamples;

function writingWith(array $rubric): array
{
    return [...ExerciseSamples::content(ExerciseType::Writing), 'rubric' => $rubric];
}

function writingErrors(array $rubric): array
{
    return payloadErrors(ExerciseType::Writing, writingWith($rubric), ExerciseSamples::solution(ExerciseType::Writing));
}

test('a valid rubric totals its criteria', function () {
    $content = WritingContent::from(writingWith(ExerciseSamples::rubric()));

    expect(writingErrors(ExerciseSamples::rubric()))->toBe([])
        ->and($content->rubric[0])->toBeInstanceOf(RubricCriterion::class)
        ->and($content->rubricTotal())->toBe(10);
});

test('the speaking rubric totals too', function () {
    $content = SpeakingContent::from(ExerciseSamples::content(ExerciseType::Speaking));

    expect($content->rubricTotal())->toBe(10);
});

test('duplicate criterion keys are rejected', function () {
    $rubric = [
        ['key' => 'fluency', 'description' => ['en' => 'Flow'], 'max_points' => 5],
        ['key' => 'fluency', 'description' => ['en' => 'Flow again'], 'max_points' => 5],
    ];

    expect(payloadErrors(ExerciseType::Speaking, [...ExerciseSamples::content(ExerciseType::Speaking), 'rubric' => $rubric], ['model_answer' => null]))
        ->toContain('content.rubric.1.key');
});

test('an empty rubric is rejected', function () {
    expect(writingErrors([]))->toContain('content.rubric');
});

test('a criterion needs an English description', function () {
    $rubric = [['key' => 'task', 'description' => ['es' => 'Tarea'], 'max_points' => 5]];

    expect(writingErrors($rubric))->toContain('content.rubric.0.description');
});

test('a criterion rejects unsupported locales', function () {
    $rubric = [['key' => 'task', 'description' => ['en' => 'Task', 'fr' => 'Tâche'], 'max_points' => 5]];

    expect(writingErrors($rubric))->toContain('content.rubric.0.description');
});

test('max points must be a positive integer', function (mixed $points) {
    $rubric = [['key' => 'task', 'description' => ['en' => 'Task'], 'max_points' => $points]];

    expect(writingErrors($rubric))->toContain('content.rubric.0.max_points');
})->with([0, -2, 'five', 2.5]);

test('any scale is allowed', function () {
    $rubric = [['key' => 'overall', 'description' => ['en' => 'Overall'], 'max_points' => 100]];

    expect(writingErrors($rubric))->toBe([])
        ->and(RubricCriterion::total(WritingContent::from(writingWith($rubric))->rubric))->toBe(100);
});
