<?php

use App\Content\ContentTables;
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

test('a row is exported by key with no ids, parents, positions or timestamps', function (string $model) {
    /** @var Model $record */
    $record = $model::factory()->create();
    $record->archive();

    $json = ContentJson::for($record->fresh());

    expect($json)->toHaveKey('key', $record->getAttribute('key'))
        ->and(array_key_first($json))->toBe('key')
        ->and($json)->not->toHaveKeys(['id', 'position', 'created_at', 'updated_at', 'archived_at', 'level', 'unit', 'lesson', 'exam', 'section'])
        ->and(collect($json)->keys()->filter(fn (string $key) => str_ends_with($key, '_id'))->all())->toBe([]);
})->with('content models');

test('the members follow the file order of their table', function (string $model) {
    /** @var Model $record */
    $record = $model::factory()->create();
    $table = ContentTables::forModel($model);

    $members = array_values(array_intersect(array_keys(ContentJson::for($record->fresh())), $table->members));

    expect($members)->toBe(array_values(array_intersect($table->members, $members)));
})->with('content models');

test('an exercise carries its type, payloads and translations', function () {
    $exercise = Exercise::factory()->multipleChoice()->create(['explanation' => ['en' => 'Third person.', 'es' => 'Tercera persona.']]);

    $json = ContentJson::for($exercise->fresh());

    expect($json)->not->toHaveKey('passage')
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

test('members without a value are left out', function () {
    $lesson = Lesson::factory()->create(['theory' => null]);

    expect(ContentJson::for($lesson->fresh()))->not->toHaveKey('theory');
});

test('a lesson nests its vocabulary and active exercises in order', function () {
    $lesson = Lesson::factory()->create();
    $item = VocabularyItem::factory()->create(['lesson_id' => $lesson->id]);
    $second = Exercise::factory()->create(['lesson_id' => $lesson->id, 'position' => 2]);
    $first = Exercise::factory()->create(['lesson_id' => $lesson->id, 'position' => 1]);
    Exercise::factory()->create(['lesson_id' => $lesson->id, 'position' => 3])->archive();

    $json = ContentJson::for($lesson->fresh());

    expect(array_keys($json))->toContain('vocabulary', 'exercises')
        ->and(array_search('vocabulary', array_keys($json), true))->toBeLessThan(array_search('exercises', array_keys($json), true))
        ->and(array_column($json['vocabulary'], 'key'))->toBe([$item->key])
        ->and(array_column($json['exercises'], 'key'))->toBe([$first->key, $second->key])
        ->and($json['exercises'][0])->not->toHaveKey('position');
});

test('a unit nests its lessons', function () {
    $unit = Unit::factory()->create();
    $lesson = Lesson::factory()->create(['unit_id' => $unit->id]);

    expect(array_column(ContentJson::for($unit->fresh())['lessons'], 'key'))->toBe([$lesson->key]);
});

test('an exam nests its sections and their questions', function () {
    $question = ExamQuestion::factory()->create();
    $section = $question->section;

    $json = ContentJson::for($section->exam->fresh());

    expect(array_column($json['sections'], 'key'))->toBe([$section->key])
        ->and(array_column($json['sections'][0]['questions'], 'key'))->toBe([$question->key]);
});

test('a level leaves its units to their own files', function () {
    $unit = Unit::factory()->create();

    expect(ContentJson::for($unit->level->fresh()))->not->toHaveKey('units');
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
