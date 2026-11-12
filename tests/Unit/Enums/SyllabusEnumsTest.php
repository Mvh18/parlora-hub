<?php

use App\Enums\CefrLevel;
use App\Enums\LessonType;
use App\Enums\VocabularyKind;

test('hub teaches CEFR levels A1 to C1 and no C2', function () {
    expect(CefrLevel::values())->toBe(['A1', 'A2', 'B1', 'B2', 'C1'])
        ->and(CefrLevel::tryFrom('C2'))->toBeNull()
        ->and(CefrLevel::tryFrom('D1'))->toBeNull();
});

test('levels are ordered from A1 to C1', function () {
    expect(CefrLevel::A1->position())->toBe(1)
        ->and(CefrLevel::B1->position())->toBe(3)
        ->and(CefrLevel::C1->position())->toBe(5);
});

test('lesson types cover the three content kinds and the four skills', function () {
    expect(LessonType::values())->toBe([
        'grammar', 'vocabulary', 'phrasal_verbs', 'reading', 'listening', 'writing', 'speaking',
    ]);
});

test('vocabulary kinds are word, phrasal verb and expression', function () {
    expect(VocabularyKind::values())->toBe(['word', 'phrasal_verb', 'expression']);
});

test('unknown values do not resolve to a case', function () {
    expect(LessonType::tryFrom('poetry'))->toBeNull()
        ->and(VocabularyKind::tryFrom('idiom'))->toBeNull();
});
