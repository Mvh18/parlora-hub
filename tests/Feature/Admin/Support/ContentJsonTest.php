<?php

use App\Filament\Support\ContentJson;
use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\Lesson;
use App\Models\Level;
use App\Models\Passage;
use App\Models\Unit;
use App\Models\VocabularyItem;
use Illuminate\Database\Eloquent\Model;

dataset('content models', [
    'level' => Level::class,
    'unit' => Unit::class,
    'lesson' => Lesson::class,
    'vocabulary item' => VocabularyItem::class,
    'passage' => Passage::class,
    'exercise' => Exercise::class,
    'exam' => Exam::class,
    'exam section' => ExamSection::class,
    'exam question' => ExamQuestion::class,
]);

test('a row is exported by key with no ids or timestamps', function (string $model) {
    /** @var Model $record */
    $record = $model::factory()->create();
    $record->archive();

    $json = ContentJson::for($record->fresh());

    expect($json)->toHaveKey('key', $record->getAttribute('key'))
        ->and(array_key_first($json))->toBe('key')
        ->and($json)->not->toHaveKeys(['id', 'created_at', 'updated_at', 'archived_at'])
        ->and(collect($json)->keys()->filter(fn (string $key) => str_ends_with($key, '_id'))->all())->toBe([]);
})->with('content models');

test('an exercise carries its lesson key, type and payloads', function () {
    $exercise = Exercise::factory()->multipleChoice()->create(['explanation' => ['en' => 'Third person.', 'es' => 'Tercera persona.']]);

    $json = ContentJson::for($exercise->fresh());

    expect($json['lesson'])->toBe($exercise->lesson->key)
        ->and($json['passage'])->toBeNull()
        ->and($json['type'])->toBe('multiple_choice')
        ->and($json['solution'])->toBe(['correct' => ['b']])
        ->and($json['content'])->toHaveKey('options')
        ->and($json['explanation'])->toBe(['en' => 'Third person.', 'es' => 'Tercera persona.']);
});

test('the passage is referenced by key', function () {
    $passage = Passage::factory()->create();
    $exercise = Exercise::factory()->create(['passage_id' => $passage->id]);

    expect(ContentJson::for($exercise->fresh())['passage'])->toBe($passage->key);
});

test('each child names its parent by key', function () {
    $question = ExamQuestion::factory()->create();
    $section = $question->section;
    $unit = Unit::factory()->create();
    $lesson = Lesson::factory()->create(['unit_id' => $unit->id]);
    $item = VocabularyItem::factory()->create(['lesson_id' => $lesson->id]);

    expect(ContentJson::for($question->fresh())['section'])->toBe($section->key)
        ->and(ContentJson::for($section->fresh())['exam'])->toBe($section->exam->key)
        ->and(ContentJson::for($unit->fresh())['level'])->toBe($unit->level->key)
        ->and(ContentJson::for($lesson->fresh())['unit'])->toBe($unit->key)
        ->and(ContentJson::for($item->fresh())['lesson'])->toBe($lesson->key);
});

test('hidden attributes are exported', function () {
    $passage = Passage::factory()->create(['transcript' => 'Hello, Anna.']);

    expect(ContentJson::for($passage->fresh())['transcript'])->toBe('Hello, Anna.');
});

test('the JSON text is pretty and keeps unicode', function () {
    $item = VocabularyItem::factory()->create(['meaning' => ['en' => 'to look for', 'es' => 'buscar algo útil']]);

    $text = ContentJson::encode($item->fresh());

    expect($text)->toContain("\n    \"key\"")
        ->and($text)->toContain('útil');
});
