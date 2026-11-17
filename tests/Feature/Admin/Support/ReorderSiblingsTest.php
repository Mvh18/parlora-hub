<?php

use App\Filament\Support\ReorderSiblings;
use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\Lesson;
use App\Models\Unit;
use App\Models\VocabularyItem;
use Illuminate\Support\Facades\DB;

/** @return list<string> keys of the unit's lessons by position */
function lessonOrder(Unit $unit): array
{
    return $unit->lessons()->orderBy('position')->pluck('key')->all();
}

test('moving the third lesson first stores the new order as positions 1…n', function () {
    $unit = Unit::factory()->create();
    $first = Lesson::factory()->for($unit)->create(['key' => 'l1', 'position' => 1]);
    $second = Lesson::factory()->for($unit)->create(['key' => 'l2', 'position' => 2]);
    $third = Lesson::factory()->for($unit)->create(['key' => 'l3', 'position' => 3]);

    ReorderSiblings::run($unit->lessons(), [$third->id, $first->id, $second->id]);

    expect(lessonOrder($unit))->toBe(['l3', 'l1', 'l2'])
        ->and($unit->lessons()->orderBy('position')->pluck('position')->all())->toBe([1, 2, 3]);
});

test('large and sparse positions are compacted without overflowing', function () {
    $unit = Unit::factory()->create();
    $a = Lesson::factory()->for($unit)->create(['key' => 'a', 'position' => 30000]);
    $b = Lesson::factory()->for($unit)->create(['key' => 'b', 'position' => 32000]);

    ReorderSiblings::run($unit->lessons(), [$b->id, $a->id]);

    expect($unit->lessons()->orderBy('position')->pluck('position', 'key')->all())->toBe(['b' => 1, 'a' => 2]);
});

test('siblings left out of the order keep their relative order after the given ones', function () {
    $unit = Unit::factory()->create();
    $one = Lesson::factory()->for($unit)->create(['key' => 'l1', 'position' => 1]);
    Lesson::factory()->for($unit)->create(['key' => 'l2', 'position' => 2]);
    $three = Lesson::factory()->for($unit)->create(['key' => 'l3', 'position' => 3]);
    $three->archive();

    ReorderSiblings::run($unit->lessons(), [$three->id, $one->id]);

    expect(lessonOrder($unit))->toBe(['l3', 'l1', 'l2']);
});

test('other parents are untouched', function () {
    $unit = Unit::factory()->create();
    $other = Unit::factory()->create();
    $mine = Lesson::factory()->for($unit)->create(['position' => 2]);
    Lesson::factory()->for($unit)->create(['position' => 1]);
    $theirs = Lesson::factory()->for($other)->create(['position' => 2]);

    ReorderSiblings::run($unit->lessons(), [$mine->id]);

    expect($theirs->fresh()->position)->toBe(2);
});

test('an id from another parent is rejected and nothing changes', function () {
    $unit = Unit::factory()->create();
    $mine = Lesson::factory()->for($unit)->create(['position' => 1]);
    $foreign = Lesson::factory()->create(['position' => 1]);

    expect(fn () => ReorderSiblings::run($unit->lessons(), [$foreign->id, $mine->id]))
        ->toThrow(InvalidArgumentException::class);

    expect($mine->fresh()->position)->toBe(1)
        ->and($foreign->fresh()->position)->toBe(1);
});

test('the reorder runs in one transaction', function () {
    $unit = Unit::factory()->create();
    $a = Lesson::factory()->for($unit)->create(['position' => 1]);
    $b = Lesson::factory()->for($unit)->create(['position' => 2]);
    $levels = [];

    DB::listen(function () use (&$levels) {
        $levels[] = DB::transactionLevel();
    });

    ReorderSiblings::run($unit->lessons(), [$b->id, $a->id]);

    // RefreshDatabase already holds one level; every write runs inside another.
    expect(min($levels))->toBeGreaterThan(1);
});

test('vocabulary, exercises and exam sections reorder too', function () {
    $lesson = Lesson::factory()->create();
    $v1 = VocabularyItem::factory()->for($lesson)->create(['position' => 1]);
    $v2 = VocabularyItem::factory()->for($lesson)->create(['position' => 2]);
    $e1 = Exercise::factory()->for($lesson)->create(['position' => 1]);
    $e2 = Exercise::factory()->for($lesson)->create(['position' => 2]);
    $exam = Exam::factory()->create();
    $s1 = ExamSection::factory()->for($exam)->create(['position' => 1]);
    $s2 = ExamSection::factory()->for($exam)->create(['position' => 2]);

    ReorderSiblings::run($lesson->vocabularyItems(), [$v2->id, $v1->id]);
    ReorderSiblings::run($lesson->exercises(), [$e2->id, $e1->id]);
    ReorderSiblings::run($exam->sections(), [$s2->id, $s1->id]);

    expect([$v2->fresh()->position, $e2->fresh()->position, $s2->fresh()->position])->toBe([1, 1, 1]);
});
