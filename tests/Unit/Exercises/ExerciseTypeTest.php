<?php

use App\Data\Exercises\ValidatesAgainstContent;
use App\Enums\ExerciseType;
use App\Enums\Skill;
use Database\Factories\Support\ExerciseSamples;
use Spatie\LaravelData\Data;

test('every type maps to existing content and solution classes', function (ExerciseType $type) {
    expect(class_exists($type->contentClass()))->toBeTrue()
        ->and(is_subclass_of($type->contentClass(), Data::class))->toBeTrue()
        ->and(class_exists($type->solutionClass()))->toBeTrue()
        ->and(is_subclass_of($type->solutionClass(), Data::class))->toBeTrue()
        ->and(is_subclass_of($type->solutionClass(), ValidatesAgainstContent::class))->toBeTrue();
})->with(ExerciseType::cases());

test('the catalogue has the eight types', function () {
    expect(ExerciseType::values())->toBe([
        'multiple_choice', 'fill_gap', 'reorder', 'matching',
        'transformation', 'dictation', 'writing', 'speaking',
    ]);
});

test('only dictation requires audio', function () {
    $requiring = array_values(array_filter(ExerciseType::cases(), fn (ExerciseType $type) => $type->requiresAudio()));

    expect($requiring)->toBe([ExerciseType::Dictation]);
});

test('skills cover the statistics axes', function () {
    expect(Skill::values())->toBe(['grammar', 'vocabulary', 'reading', 'listening', 'writing', 'speaking']);
});

test('every sample payload is valid for its type', function (ExerciseType $type) {
    expect(payloadErrors($type, ExerciseSamples::content($type), ExerciseSamples::solution($type)))->toBe([]);
})->with(ExerciseType::cases());
