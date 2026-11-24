<?php

use App\Enums\CefrLevel;
use App\Filament\Resources\Lessons\LessonResource;
use App\Filament\Resources\Lessons\Pages\EditLesson;
use App\Filament\Resources\Lessons\RelationManagers\VocabularyItemsRelationManager;
use App\Filament\Resources\VocabularyItems\Pages\EditVocabularyItem;
use App\Filament\Resources\VocabularyItems\VocabularyItemResource;
use App\Models\Lesson;
use App\Models\Level;
use App\Models\Unit;
use App\Models\User;
use App\Models\VocabularyItem;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->withTwoFactor()->create());
    Filament::setCurrentPanel('admin');
});

function lessonRelation(Lesson $lesson): array
{
    return ['ownerRecord' => $lesson, 'pageClass' => EditLesson::class];
}

test('a lesson title is edited and its key is not', function () {
    $lesson = Lesson::factory()->create(['key' => 'a1.u01.l01', 'title' => 'Present simple']);

    Livewire::test(EditLesson::class, ['record' => $lesson->getRouteKey()])
        ->assertFormSet(['key' => 'a1.u01.l01', 'title' => 'Present simple'])
        ->set('data.title', 'Present simple: be')
        ->set('data.key', 'hacked')
        ->call('save')
        ->assertHasNoFormErrors();

    expect($lesson->fresh()->title)->toBe('Present simple: be')
        ->and($lesson->fresh()->key)->toBe('a1.u01.l01');
});

test('a lesson shows one theory field per configured locale', function () {
    config(['hub.content_locales' => ['en', 'es']]);
    $lesson = Lesson::factory()->create(['theory' => ['en' => 'Use **be**.', 'es' => 'Usa **be**.']]);

    Livewire::test(EditLesson::class, ['record' => $lesson->getRouteKey()])
        ->assertFormSet(['theory.en' => 'Use **be**.', 'theory.es' => 'Usa **be**.'])
        ->assertFormFieldExists('theory.en')
        ->assertFormFieldExists('theory.es');
});

test('the Spanish theory is edited per locale', function () {
    $lesson = Lesson::factory()->create(['theory' => ['en' => 'Use be.']]);

    Livewire::test(EditLesson::class, ['record' => $lesson->getRouteKey()])
        ->set('data.theory.es', 'Usa be.')
        ->call('save')
        ->assertHasNoFormErrors();

    expect($lesson->fresh()->getTranslation('theory', 'es'))->toBe('Usa be.')
        ->and($lesson->fresh()->getTranslation('theory', 'en'))->toBe('Use be.');
});

test('the re-import notice and Copy as JSON are on the lesson page as a file fragment', function () {
    $lesson = Lesson::factory()->create();

    Livewire::test(EditLesson::class, ['record' => $lesson->getRouteKey()])
        ->assertSee(__('admin.reimport_notice.heading'))
        ->mountAction('copyAsJson')
        ->assertMountedActionModalSee([$lesson->key, $lesson->title])
        ->assertMountedActionModalDontSee([$lesson->unit->key, 'unit_id', 'created_at']);
});

test('a lesson is archived and restored from its page', function () {
    $lesson = Lesson::factory()->create();

    Livewire::test(EditLesson::class, ['record' => $lesson->getRouteKey()])
        ->callAction('archive');
    expect($lesson->fresh()->isArchived())->toBeTrue();

    Livewire::test(EditLesson::class, ['record' => $lesson->getRouteKey()])
        ->callAction('restore');
    expect($lesson->fresh()->isArchived())->toBeFalse();
});

test('a lesson moves to another unit at a free position', function () {
    $lesson = Lesson::factory()->create(['position' => 1]);
    $target = Unit::factory()->create();

    Livewire::test(EditLesson::class, ['record' => $lesson->getRouteKey()])
        ->set('data.unit_id', $target->id)
        ->set('data.position', 5)
        ->call('save')
        ->assertHasNoFormErrors();

    expect($lesson->fresh()->unit_id)->toBe($target->id)
        ->and($lesson->fresh()->position)->toBe(5);
});

test('a lesson moved onto a taken position is a form error, not a database error', function () {
    $target = Unit::factory()->create();
    Lesson::factory()->for($target)->create(['position' => 1]);
    $lesson = Lesson::factory()->create(['position' => 1]);
    $original = $lesson->unit_id;

    Livewire::test(EditLesson::class, ['record' => $lesson->getRouteKey()])
        ->set('data.unit_id', $target->id)
        ->call('save')
        ->assertHasFormErrors(['position']);

    expect($lesson->fresh()->unit_id)->toBe($original);
});

test('a lesson keeps its own position when it is saved without moving', function () {
    $lesson = Lesson::factory()->create(['position' => 2]);

    Livewire::test(EditLesson::class, ['record' => $lesson->getRouteKey()])
        ->call('save')
        ->assertHasNoFormErrors();
});

test('the vocabulary relation lists a lesson\'s items ordered by position', function () {
    $lesson = Lesson::factory()->create();
    $second = VocabularyItem::factory()->for($lesson)->create(['position' => 2]);
    $first = VocabularyItem::factory()->for($lesson)->create(['position' => 1]);
    $other = VocabularyItem::factory()->create(['position' => 1]);

    Livewire::test(VocabularyItemsRelationManager::class, lessonRelation($lesson))
        ->assertCanSeeTableRecords([$first, $second], inOrder: true)
        ->assertCanNotSeeTableRecords([$other]);
});

test('vocabulary items are reordered, archived and restored in the relation', function () {
    $lesson = Lesson::factory()->create();
    $one = VocabularyItem::factory()->for($lesson)->create(['position' => 1]);
    $two = VocabularyItem::factory()->for($lesson)->create(['position' => 2]);

    Livewire::test(VocabularyItemsRelationManager::class, lessonRelation($lesson))
        ->call('reorderTable', [$two->id, $one->id])
        ->callAction(TestAction::make('archive')->table($one))
        ->removeTableFilter('archived')
        ->callAction(TestAction::make('restore')->table($one));

    expect($two->fresh()->position)->toBe(1)
        ->and($one->fresh()->position)->toBe(2)
        ->and($one->fresh()->isArchived())->toBeFalse();
});

test('the Spanish meaning of a vocabulary item is saved', function () {
    $item = VocabularyItem::factory()->create(['meaning' => ['en' => 'to look for']]);

    Livewire::test(EditVocabularyItem::class, ['record' => $item->getRouteKey()])
        ->set('data.meaning.es', 'buscar')
        ->call('save')
        ->assertHasNoFormErrors();

    expect($item->fresh()->getTranslation('meaning', 'es'))->toBe('buscar');
});

test('the English meaning of a vocabulary item is required', function () {
    $item = VocabularyItem::factory()->create(['meaning' => ['en' => 'to look for', 'es' => 'buscar']]);

    Livewire::test(EditVocabularyItem::class, ['record' => $item->getRouteKey()])
        ->set('data.meaning.en', '')
        ->call('save')
        ->assertHasFormErrors(['meaning.en' => 'required']);

    expect($item->fresh()->getTranslation('meaning', 'en'))->toBe('to look for');
});

test('clearing the Spanish meaning removes it', function () {
    $item = VocabularyItem::factory()->create(['meaning' => ['en' => 'to look for', 'es' => 'buscar']]);

    Livewire::test(EditVocabularyItem::class, ['record' => $item->getRouteKey()])
        ->set('data.meaning.es', '')
        ->call('save')
        ->assertHasNoFormErrors();

    expect($item->fresh()->getTranslations('meaning'))->toBe(['en' => 'to look for']);
});

test('a vocabulary item key is not editable and its page offers no delete', function () {
    $item = VocabularyItem::factory()->create(['key' => 'a1.v01']);

    Livewire::test(EditVocabularyItem::class, ['record' => $item->getRouteKey()])
        ->set('data.key', 'hacked')
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertActionDoesNotExist('delete');

    expect($item->fresh()->key)->toBe('a1.v01');
});

test('the lesson and vocabulary pages open for an admin and are denied to a learner', function () {
    $lesson = Lesson::factory()->create();
    $item = VocabularyItem::factory()->for($lesson)->create();

    $this->get(LessonResource::getUrl('edit', ['record' => $lesson]))->assertOk();
    $this->get(VocabularyItemResource::getUrl('edit', ['record' => $item]))->assertOk();

    $this->actingAs(User::factory()->create());

    $this->get(LessonResource::getUrl('edit', ['record' => $lesson]))->assertForbidden();
    $this->get(VocabularyItemResource::getUrl('edit', ['record' => $item]))->assertForbidden();
});

test('a lesson page links back to its unit and a unit page to its level', function () {
    $level = Level::factory()->code(CefrLevel::A1)->create();
    $unit = Unit::factory()->for($level)->create();
    $lesson = Lesson::factory()->for($unit)->create();

    Livewire::test(EditLesson::class, ['record' => $lesson->getRouteKey()])
        ->assertActionVisible('back');
});
