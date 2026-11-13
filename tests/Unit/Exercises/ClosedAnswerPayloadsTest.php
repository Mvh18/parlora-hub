<?php

use App\Data\Exercises\FillGapContent;
use App\Data\Exercises\MultipleChoiceContent;
use App\Enums\ExerciseType;
use Database\Factories\Support\ExerciseSamples;

function sample(ExerciseType $type, array $content = [], array $solution = []): array
{
    return payloadErrors(
        $type,
        [...ExerciseSamples::content($type), ...$content],
        [...ExerciseSamples::solution($type), ...$solution],
    );
}

describe('multiple choice', function () {
    test('a valid exercise passes and reads back as typed data', function () {
        expect(sample(ExerciseType::MultipleChoice))->toBe([])
            ->and(MultipleChoiceContent::from(ExerciseSamples::content(ExerciseType::MultipleChoice))->options)->toHaveCount(3);
    });

    test('the answer must be one of the options', function () {
        expect(sample(ExerciseType::MultipleChoice, solution: ['correct' => ['d']]))->toBe(['solution.correct.0']);
    });

    test('only one answer unless multiple', function () {
        expect(sample(ExerciseType::MultipleChoice, solution: ['correct' => ['a', 'b']]))->toBe(['solution.correct']);
    });

    test('several answers are fine when multiple', function () {
        expect(sample(ExerciseType::MultipleChoice, ['multiple' => true], ['correct' => ['a', 'b']]))->toBe([]);
    });

    test('needs between two and eight options', function () {
        expect(sample(ExerciseType::MultipleChoice, ['options' => [['id' => 'a', 'text' => 'x']]], ['correct' => ['a']]))
            ->toContain('content.options');

        $nine = array_map(fn (int $i) => ['id' => "o{$i}", 'text' => "t{$i}"], range(1, 9));
        expect(sample(ExerciseType::MultipleChoice, ['options' => $nine], ['correct' => ['o1']]))->toContain('content.options');
    });

    test('option ids must be unique and well formed', function () {
        $options = [['id' => 'a', 'text' => 'x'], ['id' => 'a', 'text' => 'y'], ['id' => 'b c', 'text' => 'z']];

        expect(sample(ExerciseType::MultipleChoice, ['options' => $options], ['correct' => ['a']]))
            ->toContain('content.options.1.id', 'content.options.2.id');
    });

    test('an answer is required', function () {
        expect(sample(ExerciseType::MultipleChoice, solution: ['correct' => []]))->toContain('solution.correct');
    });
});

describe('fill gap', function () {
    test('a valid exercise passes', function () {
        expect(sample(ExerciseType::FillGap))->toBe([])
            ->and(FillGapContent::from(ExerciseSamples::content(ExerciseType::FillGap))->gapIds())->toBe(['g1', 'g2']);
    });

    test('every gap needs answers', function () {
        expect(sample(ExerciseType::FillGap, solution: ['answers' => ['g1' => ['goes']]]))->toBe(['solution.answers.g2']);
    });

    test('answers without a gap are rejected', function () {
        expect(sample(ExerciseType::FillGap, solution: ['answers' => ['g1' => ['goes'], 'g2' => ['gets'], 'g9' => ['x']]]))
            ->toBe(['solution.answers.g9']);
    });

    test('the text needs at least one marker', function () {
        expect(sample(ExerciseType::FillGap, ['text' => 'No gaps here.']))->toContain('content.text');
    });

    test('a gap needs at least one non-empty answer', function () {
        expect(sample(ExerciseType::FillGap, solution: ['answers' => ['g1' => [], 'g2' => ['gets']]]))->toContain('solution.answers.g1')
            ->and(sample(ExerciseType::FillGap, solution: ['answers' => ['g1' => [''], 'g2' => ['gets']]]))->toContain('solution.answers.g1.0');
    });
});

describe('reorder', function () {
    test('a valid exercise passes', function () {
        expect(sample(ExerciseType::Reorder))->toBe([]);
    });

    test('an accepted order must be a permutation of the tokens', function () {
        expect(sample(ExerciseType::Reorder, solution: ['orders' => [['t1', 't2']]]))->toBe(['solution.orders.0'])
            ->and(sample(ExerciseType::Reorder, solution: ['orders' => [['t1', 't2', 't2']]]))->toBe(['solution.orders.0'])
            ->and(sample(ExerciseType::Reorder, solution: ['orders' => [['t1', 't2', 'tX']]]))->toBe(['solution.orders.0']);
    });

    test('the stored token order must not reveal an accepted order', function () {
        $tokens = [['id' => 't1', 'text' => 'She'], ['id' => 't2', 'text' => 'likes'], ['id' => 't3', 'text' => 'tea']];

        expect(sample(ExerciseType::Reorder, ['tokens' => $tokens]))->toBe(['content.tokens']);
    });

    test('several accepted orders are allowed', function () {
        expect(sample(ExerciseType::Reorder, solution: ['orders' => [['t1', 't2', 't3'], ['t3', 't1', 't2']]]))->toBe([]);
    });

    test('needs at least two tokens and one order', function () {
        expect(sample(ExerciseType::Reorder, ['tokens' => [['id' => 't1', 'text' => 'x']]], ['orders' => [['t1']]]))
            ->toContain('content.tokens');

        expect(sample(ExerciseType::Reorder, solution: ['orders' => []]))->toContain('solution.orders');
    });
});

describe('matching', function () {
    test('a valid exercise passes, with distractors on the right', function () {
        expect(sample(ExerciseType::Matching))->toBe([]);
    });

    test('pairs must reference existing ids', function () {
        expect(sample(ExerciseType::Matching, solution: ['pairs' => [['l9', 'r1'], ['l2', 'r2']]]))->toBe(['solution.pairs.0'])
            ->and(sample(ExerciseType::Matching, solution: ['pairs' => [['l1', 'r9'], ['l2', 'r2']]]))->toBe(['solution.pairs.0']);
    });

    test('a left item is paired once', function () {
        expect(sample(ExerciseType::Matching, solution: ['pairs' => [['l1', 'r1'], ['l1', 'r2'], ['l2', 'r3']]]))->toBe(['solution.pairs.1']);
    });

    test('every left item needs a pair', function () {
        expect(sample(ExerciseType::Matching, solution: ['pairs' => [['l1', 'r1']]]))->toBe(['solution.pairs']);
    });

    test('a pair has exactly two ids', function () {
        expect(sample(ExerciseType::Matching, solution: ['pairs' => [['l1'], ['l2', 'r2']]]))->toContain('solution.pairs.0');
    });
});
