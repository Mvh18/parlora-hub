<?php

use App\Models\Lesson;
use App\Models\VocabularyItem;
use Illuminate\Validation\ValidationException;

test('a spanish gloss is stored and read back', function () {
    $item = VocabularyItem::factory()->create([
        'meaning' => ['en' => 'to look for', 'es' => 'buscar'],
    ])->fresh();

    expect($item->getTranslation('meaning', 'es'))->toBe('buscar')
        ->and($item->getTranslation('meaning', 'en'))->toBe('to look for');
});

test('a missing locale falls back to english', function () {
    $item = VocabularyItem::factory()->create(['meaning' => ['en' => 'to look for']])->fresh();

    expect($item->getTranslation('meaning', 'es'))->toBe('to look for');
});

test('english is required', function () {
    try {
        VocabularyItem::factory()->create(['meaning' => ['es' => 'buscar']]);
        $this->fail('Expected a ValidationException.');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('meaning.en');
    }

    expect(VocabularyItem::count())->toBe(0);
});

test('a locale outside hub.content_locales is rejected', function () {
    try {
        Lesson::factory()->create(['theory' => ['en' => 'Present simple', 'fr' => 'Présent simple']]);
        $this->fail('Expected a ValidationException.');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('theory.fr');
    }
});

test('translations are validated on update too', function () {
    $item = VocabularyItem::factory()->create(['meaning' => ['en' => 'to look for', 'es' => 'buscar']]);

    $item->forgetTranslation('meaning', 'en');

    expect(fn () => $item->save())->toThrow(ValidationException::class);
    expect($item->fresh()->getTranslation('meaning', 'en'))->toBe('to look for');
});

test('meaning is required but theory is optional', function () {
    $lesson = Lesson::factory()->create(['theory' => null]);

    expect($lesson->fresh()->getTranslations('theory'))->toBe([]);

    expect(fn () => VocabularyItem::factory()->create(['meaning' => null]))->toThrow(ValidationException::class);
});

test('theory keeps markdown per locale', function () {
    $lesson = Lesson::factory()->create([
        'theory' => ['en' => "## Present simple\n\nWe use it for **habits**.", 'es' => '## Presente simple'],
    ])->fresh();

    expect($lesson->getTranslation('theory', 'en'))->toContain('**habits**')
        ->and($lesson->getTranslation('theory', 'es'))->toBe('## Presente simple');
});
