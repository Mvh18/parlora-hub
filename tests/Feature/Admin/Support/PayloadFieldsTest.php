<?php

use App\Enums\ExerciseType;
use App\Filament\Support\PayloadFields;
use App\Models\ExamQuestion;
use App\Models\Exercise;
use App\Models\User;
use Filament\Forms\Components\CodeEditor;
use Filament\Forms\Components\Select;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\Support\Admin\RecordForm;

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->withTwoFactor()->create());

    RecordForm::using(fn () => [
        Select::make('type')->options(ExerciseType::class),
        ...PayloadFields::make(),
    ]);
});

function payloadForm(Exercise|ExamQuestion $record): Testable
{
    return Livewire::test(RecordForm::class, ['record' => $record]);
}

/** @param  array<string, mixed>  $payload */
function json(array $payload): string
{
    return json_encode($payload, JSON_PRETTY_PRINT);
}

test('content and solution are JSON code editors', function () {
    $fields = PayloadFields::make();

    expect(collect($fields)->map->getName()->all())->toBe(['content', 'solution'])
        ->and($fields)->each->toBeInstanceOf(CodeEditor::class);
});

test('the editors load the stored payload as JSON', function () {
    $exercise = Exercise::factory()->multipleChoice()->create();

    $form = payloadForm($exercise);

    expect(json_decode($form->get('data.solution'), true))->toBe(['correct' => ['b']])
        ->and(json_decode($form->get('data.content'), true))->toHaveKey('options');
});

test('a valid payload is saved', function () {
    $exercise = Exercise::factory()->multipleChoice()->create();

    payloadForm($exercise)
        ->set('data.solution', json(['correct' => ['a']]))
        ->call('save')
        ->assertHasNoErrors();

    expect($exercise->fresh()->solution->toArray())->toBe(['correct' => ['a']]);
});

test('an invalid payload is rejected with its path on the solution field', function () {
    $exercise = Exercise::factory()->multipleChoice()->create();

    $form = payloadForm($exercise)
        ->set('data.solution', json(['correct' => ['d']]))
        ->call('save')
        ->assertHasErrors(['data.solution'])
        ->assertHasNoErrors(['data.content']);

    expect($form->errors()->first('data.solution'))->toContain('solution.correct.0')
        ->and($exercise->fresh()->solution->toArray())->toBe(['correct' => ['b']]);
});

test('a content error lands on the content field', function () {
    $exercise = Exercise::factory()->multipleChoice()->create();

    $form = payloadForm($exercise)
        ->set('data.content', json(['options' => []]))
        ->call('save')
        ->assertHasErrors(['data.content']);

    expect($form->errors()->first('data.content'))->toContain('content.options');
});

test('malformed JSON is rejected on its field', function () {
    $exercise = Exercise::factory()->multipleChoice()->create();
    $before = $exercise->fresh()->getRawOriginal('content');

    payloadForm($exercise)
        ->set('data.content', '{"options": [')
        ->call('save')
        ->assertHasErrors(['data.content'])
        ->assertHasNoErrors(['data.solution']);

    expect($exercise->fresh()->getRawOriginal('content'))->toBe($before);
});

test('a JSON value that is not an object is rejected', function () {
    payloadForm(Exercise::factory()->multipleChoice()->create())
        ->set('data.solution', '["a"]')
        ->call('save')
        ->assertHasErrors(['data.solution']);
});

test('changing the type without a matching payload fails', function () {
    $exercise = Exercise::factory()->multipleChoice()->create();

    payloadForm($exercise)
        ->set('data.type', ExerciseType::FillGap->value)
        ->call('save')
        ->assertHasErrors(['data.content']);

    expect($exercise->fresh()->type)->toBe(ExerciseType::MultipleChoice);
});

test('exam questions use the same contract', function () {
    $question = ExamQuestion::factory()->create();

    payloadForm($question)
        ->set('data.solution', json(['correct' => ['zz']]))
        ->call('save')
        ->assertHasErrors(['data.solution']);
});
