<?php

use App\Filament\Support\TranslatableFields;
use App\Models\Lesson;
use App\Models\User;
use App\Models\VocabularyItem;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Textarea;
use Livewire\Livewire;
use Tests\Support\Admin\RecordForm;

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->withTwoFactor()->create());
});

test('one field per configured locale', function () {
    config(['hub.content_locales' => ['en', 'es']]);

    $fields = TranslatableFields::fields('meaning');

    expect(collect($fields)->map->getName()->all())->toBe(['meaning.en', 'meaning.es'])
        ->and($fields)->each->toBeInstanceOf(Textarea::class);
});

test('the fields follow the configuration', function () {
    config(['hub.content_locales' => ['en']]);

    expect(collect(TranslatableFields::fields('meaning'))->map->getName()->all())->toBe(['meaning.en']);
});

test('the markdown variant uses markdown editors', function () {
    expect(TranslatableFields::fields('theory', markdown: true))->each->toBeInstanceOf(MarkdownEditor::class);
});

test('the form loads the stored translations', function () {
    $item = VocabularyItem::factory()->create(['meaning' => ['en' => 'to look for', 'es' => 'buscar']]);
    RecordForm::using(fn () => [TranslatableFields::make('meaning', required: true)]);

    Livewire::test(RecordForm::class, ['record' => $item])
        ->assertSet('data.meaning.en', 'to look for')
        ->assertSet('data.meaning.es', 'buscar');
});

test('a spanish gloss is saved', function () {
    $item = VocabularyItem::factory()->create(['meaning' => ['en' => 'to look for']]);
    RecordForm::using(fn () => [TranslatableFields::make('meaning', required: true)]);

    Livewire::test(RecordForm::class, ['record' => $item])
        ->set('data.meaning.es', 'buscar')
        ->call('save')
        ->assertHasNoErrors();

    expect($item->fresh()->getTranslation('meaning', 'es'))->toBe('buscar');
});

test('english is required when the attribute is', function () {
    $item = VocabularyItem::factory()->create(['meaning' => ['en' => 'to look for', 'es' => 'buscar']]);
    RecordForm::using(fn () => [TranslatableFields::make('meaning', required: true)]);

    Livewire::test(RecordForm::class, ['record' => $item])
        ->set('data.meaning.en', '')
        ->call('save')
        ->assertHasErrors(['data.meaning.en' => 'required']);

    expect($item->fresh()->getTranslation('meaning', 'en'))->toBe('to look for');
});

test('every locale is optional when the attribute is', function () {
    $lesson = Lesson::factory()->create(['theory' => ['en' => 'Present simple']]);
    RecordForm::using(fn () => [TranslatableFields::make('theory', markdown: true)]);

    Livewire::test(RecordForm::class, ['record' => $lesson])
        ->set('data.theory.en', '')
        ->call('save')
        ->assertHasNoErrors();

    expect($lesson->fresh()->getTranslations('theory'))->toBe([]);
});

test('a cleared optional locale is removed, not stored empty', function () {
    $item = VocabularyItem::factory()->create(['meaning' => ['en' => 'to look for', 'es' => 'buscar']]);
    RecordForm::using(fn () => [TranslatableFields::make('meaning', required: true)]);

    Livewire::test(RecordForm::class, ['record' => $item])
        ->set('data.meaning.es', '')
        ->call('save')
        ->assertHasNoErrors();

    expect(json_decode($item->fresh()->getRawOriginal('meaning'), true))->toBe(['en' => 'to look for']);
});
