<?php

use App\Content\SchemaValidator;
use App\Enums\ExerciseType;
use Database\Factories\Support\ExerciseSamples;

/*
 * The JSON Schema payload definitions duplicate the shape of the data classes
 * in App\Data\Exercises. These tests keep the two from drifting: whatever the
 * factories build must pass both, and one known-invalid sample per type must
 * fail both.
 */

/**
 * The same payload as an exercise inside a unit file, and as a question inside
 * an exam file.
 *
 * @param  array<string, mixed>  $content
 * @param  array<string, mixed>  $solution
 * @return array{unit: array<string, mixed>, exam: array<string, mixed>}
 */
function documentsFor(ExerciseType $type, array $content, array $solution): array
{
    return [
        'unit' => [
            'key' => 'a1.u01',
            'title' => 'Unit',
            'lessons' => [[
                'key' => 'a1.u01.l01',
                'type' => 'grammar',
                'title' => 'Lesson',
                'exercises' => [[
                    'key' => 'a1.u01.l01.ex01',
                    'type' => $type->value,
                    'skill' => 'grammar',
                    'prompt' => 'Do it.',
                    'content' => $content,
                    'solution' => $solution,
                ]],
            ]],
        ],
        'exam' => [
            'key' => 'a1.exam',
            'title' => 'Exam',
            'time_limit_minutes' => 30,
            'pass_score' => 70,
            'cooldown_hours' => 0,
            'sections' => [[
                'key' => 'a1.exam.s1',
                'skill' => 'use_of_english',
                'points' => 10,
                'grading_mode' => 'auto',
                'questions' => [[
                    'key' => 'a1.exam.s1.q1',
                    'type' => $type->value,
                    'points' => 1,
                    'content' => $content,
                    'solution' => $solution,
                ]],
            ]],
        ],
    ];
}

/** The content that every type's schema and data class must reject. */
function invalidContent(ExerciseType $type): array
{
    return match ($type) {
        ExerciseType::MultipleChoice => ['options' => [['id' => 'a', 'text' => 'only one']]],
        ExerciseType::FillGap => ['text' => ''],
        ExerciseType::Reorder => ['tokens' => [['id' => 't1', 'text' => 'alone']]],
        ExerciseType::Matching => ['left' => [['id' => 'l1', 'text' => 'dog']], 'right' => ExerciseSamples::content($type)['right']],
        ExerciseType::Transformation => [...ExerciseSamples::content($type), 'max_words' => 0],
        ExerciseType::Dictation => ['max_plays' => 0],
        ExerciseType::Writing => [...ExerciseSamples::content($type), 'rubric' => []],
        ExerciseType::Speaking => [...ExerciseSamples::content($type), 'max_seconds' => 1],
    };
}

function acceptedByDataClasses(ExerciseType $type, array $content, array $solution): bool
{
    return payloadErrors($type, $content, $solution) === [];
}

test('every exercise type has a schema-valid and class-valid factory payload', function (ExerciseType $type) {
    $content = ExerciseSamples::content($type);
    $solution = ExerciseSamples::solution($type);
    $documents = documentsFor($type, $content, $solution);
    $validator = new SchemaValidator;

    expect(acceptedByDataClasses($type, $content, $solution))->toBeTrue()
        ->and($validator->validateArray('unit', $documents['unit']))->toBe([])
        ->and($validator->validateArray('exam', $documents['exam']))->toBe([]);
})->with(ExerciseType::cases());

test('a known-invalid payload per type fails both the schema and the data classes', function (ExerciseType $type) {
    $content = invalidContent($type);
    $solution = ExerciseSamples::solution($type);
    $documents = documentsFor($type, $content, $solution);
    $validator = new SchemaValidator;

    $unitPointers = array_map(fn ($error) => $error->pointer, $validator->validateArray('unit', $documents['unit']));
    $examPointers = array_map(fn ($error) => $error->pointer, $validator->validateArray('exam', $documents['exam']));

    expect(acceptedByDataClasses($type, $content, $solution))->toBeFalse()
        ->and($unitPointers)->not->toBe([])
        ->and($unitPointers[0])->toStartWith('/lessons/0/exercises/0/content')
        ->and($examPointers)->not->toBe([])
        ->and($examPointers[0])->toStartWith('/sections/0/questions/0/content');
})->with(ExerciseType::cases());

test('the schema rejects a payload of another type', function () {
    $documents = documentsFor(
        ExerciseType::FillGap,
        ExerciseSamples::content(ExerciseType::MultipleChoice),
        ExerciseSamples::solution(ExerciseType::FillGap),
    );

    $pointers = array_map(fn ($error) => $error->pointer, (new SchemaValidator)->validateArray('unit', $documents['unit']));

    expect($pointers)->not->toBe([])
        ->and($pointers[0])->toStartWith('/lessons/0/exercises/0/content');
});

test('an unknown exercise type is rejected', function () {
    $documents = documentsFor(ExerciseType::FillGap, [], []);
    $documents['unit']['lessons'][0]['exercises'][0]['type'] = 'telepathy';

    $pointers = array_map(fn ($error) => $error->pointer, (new SchemaValidator)->validateArray('unit', $documents['unit']));

    expect($pointers)->toContain('/lessons/0/exercises/0/type');
});
