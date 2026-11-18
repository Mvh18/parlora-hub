<?php

use App\Enums\ExerciseType;
use App\Enums\Skill;
use App\Filament\Resources\Exercises\ExerciseResource;
use App\Filament\Resources\Exercises\Pages\EditExercise;
use App\Filament\Resources\Lessons\Pages\EditLesson;
use App\Filament\Resources\Lessons\RelationManagers\ExercisesRelationManager;
use App\Models\Exercise;
use App\Models\Lesson;
use App\Models\Passage;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->withTwoFactor()->create());
    Filament::setCurrentPanel('admin');
});

function exercisePage(Exercise $exercise)
{
    return Livewire::test(EditExercise::class, ['record' => $exercise->getRouteKey()]);
}

/** @return array<string, mixed> */
function storedPayload(Exercise $exercise, string $side): array
{
    return json_decode($exercise->fresh()->getRawOriginal($side), true);
}

test('the exercises relation lists a lesson\'s exercises ordered by position', function () {
    $lesson = Lesson::factory()->create();
    $second = Exercise::factory()->for($lesson)->create(['position' => 2]);
    $first = Exercise::factory()->for($lesson)->create(['position' => 1]);
    $other = Exercise::factory()->create(['position' => 1]);

    Livewire::test(ExercisesRelationManager::class, ['ownerRecord' => $lesson, 'pageClass' => EditLesson::class])
        ->assertCanSeeTableRecords([$first, $second], inOrder: true)
        ->assertCanNotSeeTableRecords([$other]);
});

test('exercises are reordered, archived and restored in the relation', function () {
    $lesson = Lesson::factory()->create();
    $one = Exercise::factory()->for($lesson)->create(['position' => 1]);
    $two = Exercise::factory()->for($lesson)->create(['position' => 2]);

    Livewire::test(ExercisesRelationManager::class, ['ownerRecord' => $lesson, 'pageClass' => EditLesson::class])
        ->call('reorderTable', [$two->id, $one->id])
        ->callAction(TestAction::make('archive')->table($one))
        ->removeTableFilter('archived')
        ->assertCanSeeTableRecords([$one, $two]);

    expect($two->fresh()->position)->toBe(1)
        ->and($one->fresh()->isArchived())->toBeTrue();
});

test('an exercise is archived and restored from its page', function () {
    $exercise = Exercise::factory()->create();

    exercisePage($exercise)->callAction('archive');
    expect($exercise->fresh()->isArchived())->toBeTrue();

    exercisePage($exercise->fresh())->callAction('restore');
    expect($exercise->fresh()->isArchived())->toBeFalse();
});

test('an exercise form loads its type, skill, prompt and both payloads as JSON', function () {
    $exercise = Exercise::factory()->create();

    exercisePage($exercise)
        ->assertFormSet(['type' => 'multiple_choice', 'skill' => 'grammar', 'prompt' => $exercise->prompt])
        ->assertFormFieldExists('content')
        ->assertFormFieldExists('solution');
});

test('the prompt, skill and English explanation are edited', function () {
    $exercise = Exercise::factory()->create();

    exercisePage($exercise)
        ->set('data.prompt', 'Pick the right verb.')
        ->set('data.skill', Skill::Vocabulary->value)
        ->set('data.explanation.en', 'Use the third person -s.')
        ->set('data.explanation.es', 'Usa la -s de tercera persona.')
        ->call('save')
        ->assertHasNoFormErrors();

    $exercise->refresh();

    expect($exercise->prompt)->toBe('Pick the right verb.')
        ->and($exercise->skill)->toBe(Skill::Vocabulary)
        ->and($exercise->getTranslation('explanation', 'es'))->toBe('Usa la -s de tercera persona.');
});

test('a valid solution is saved', function () {
    $exercise = Exercise::factory()->create();
    $content = storedPayload($exercise, 'content');
    $other = collect($content['options'])->pluck('id')->first(fn (string $id): bool => $id !== storedPayload($exercise, 'solution')['correct'][0]);

    exercisePage($exercise)
        ->set('data.solution', json_encode(['correct' => [$other]]))
        ->call('save')
        ->assertHasNoFormErrors();

    expect(storedPayload($exercise, 'solution')['correct'])->toBe([$other]);
});

test('a solution whose correct id is not an option is rejected with its path', function () {
    $exercise = Exercise::factory()->create();
    $before = storedPayload($exercise, 'solution');

    exercisePage($exercise)
        ->set('data.solution', json_encode(['correct' => ['zzz']]))
        ->call('save')
        ->assertHasFormErrors(['solution']);

    expect(storedPayload($exercise, 'solution'))->toBe($before);

    $errors = exercisePage($exercise)
        ->set('data.solution', json_encode(['correct' => ['zzz']]))
        ->call('save')
        ->errors();

    expect((string) $errors->first('data.solution'))->toContain('solution.correct.0');
});

test('malformed JSON is rejected on its field', function () {
    $exercise = Exercise::factory()->create();
    $before = storedPayload($exercise, 'content');

    exercisePage($exercise)
        ->set('data.content', '{"options": [')
        ->call('save')
        ->assertHasFormErrors(['content']);

    expect(storedPayload($exercise, 'content'))->toBe($before);
});

test('a type change without matching payloads is rejected', function () {
    $exercise = Exercise::factory()->create();

    exercisePage($exercise)
        ->set('data.type', ExerciseType::FillGap->value)
        ->call('save')
        ->assertHasFormErrors();

    expect($exercise->fresh()->type)->toBe(ExerciseType::MultipleChoice);
});

test('a different passage is attached', function () {
    $exercise = Exercise::factory()->create();
    $passage = Passage::factory()->create();

    exercisePage($exercise)
        ->set('data.passage_id', $passage->id)
        ->call('save')
        ->assertHasNoFormErrors();

    expect($exercise->fresh()->passage_id)->toBe($passage->id);
});

test('the audio path is validated', function () {
    $exercise = Exercise::factory()->create();

    exercisePage($exercise)
        ->set('data.audio_path', '../secret.mp3')
        ->call('save')
        ->assertHasFormErrors(['audio_path']);

    exercisePage($exercise)
        ->set('data.audio_path', 'a1/greeting.mp3')
        ->call('save')
        ->assertHasNoFormErrors();

    expect($exercise->fresh()->audio_path)->toBe('a1/greeting.mp3');
});

test('an exercise key is not editable', function () {
    $exercise = Exercise::factory()->create(['key' => 'a1.u01.l01.e01']);

    exercisePage($exercise)
        ->set('data.key', 'hacked')
        ->call('save')
        ->assertHasNoFormErrors();

    expect($exercise->fresh()->key)->toBe('a1.u01.l01.e01');
});

test('Copy as JSON on an exercise uses keys and the notice is shown', function () {
    $passage = Passage::factory()->create();
    $exercise = Exercise::factory()->create(['passage_id' => $passage->id]);

    exercisePage($exercise)
        ->assertSee(__('admin.reimport_notice.heading'))
        ->mountAction('copyAsJson')
        ->assertMountedActionModalSee([$exercise->key, $exercise->lesson->key, $passage->key, 'solution'])
        ->assertMountedActionModalDontSee(['lesson_id', 'passage_id', 'created_at']);
});

test('an exercise page offers no delete and its resource no creation', function () {
    $exercise = Exercise::factory()->create();

    exercisePage($exercise)->assertActionDoesNotExist('delete');

    expect(ExerciseResource::canCreate())->toBeFalse();
});

test('a learner is denied the exercise page', function () {
    $exercise = Exercise::factory()->create();

    $this->actingAs(User::factory()->create());

    $this->get(ExerciseResource::getUrl('edit', ['record' => $exercise]))->assertForbidden();
});
