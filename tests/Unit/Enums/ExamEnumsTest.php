<?php

use App\Enums\ExamKind;
use App\Enums\ExamSkill;
use App\Enums\GradingMode;

test('exams are either level or placement exams', function () {
    expect(ExamKind::values())->toBe(['level', 'placement'])
        ->and(ExamKind::tryFrom('final'))->toBeNull();
});

test('exam sections cover use of English and the four skills', function () {
    expect(ExamSkill::values())->toBe([
        'use_of_english', 'reading', 'listening', 'writing', 'speaking',
    ])->and(ExamSkill::tryFrom('grammar'))->toBeNull();
});

test('grading modes are auto, ai and self assessment', function () {
    expect(GradingMode::values())->toBe(['auto', 'ai', 'self_assessment'])
        ->and(GradingMode::from('self_assessment'))->toBe(GradingMode::SelfAssessment)
        ->and(GradingMode::tryFrom('manual'))->toBeNull();
});
