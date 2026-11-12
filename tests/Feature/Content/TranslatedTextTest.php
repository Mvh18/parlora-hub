<?php

use App\Rules\TranslatedText;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

test('english alone is valid', function () {
    expect(TranslatedText::errors(['en' => 'to look for']))->toBe([]);
});

test('english plus an allowed locale is valid', function () {
    expect(TranslatedText::errors(['en' => 'to look for', 'es' => 'buscar']))->toBe([]);
});

test('english is required', function (mixed $value) {
    expect(TranslatedText::errors($value))->toHaveKey('en');
})->with([
    'only spanish' => [['es' => 'buscar']],
    'empty array' => [[]],
    'blank english' => [['en' => '   ', 'es' => 'buscar']],
    'null english' => [['en' => null]],
]);

test('locales outside hub.content_locales are rejected', function () {
    $errors = TranslatedText::errors(['en' => 'hello', 'fr' => 'bonjour']);

    expect($errors)->toHaveKey('fr')->not->toHaveKey('en');
});

test('the allow-list follows the configuration', function () {
    config(['hub.content_locales' => ['en', 'es', 'fr']]);

    expect(TranslatedText::errors(['en' => 'hello', 'fr' => 'bonjour']))->toBe([]);
});

test('every translation must be non-empty text', function () {
    expect(TranslatedText::errors(['en' => 'hello', 'es' => 42]))->toHaveKey('es')
        ->and(TranslatedText::errors(['en' => 'hello', 'es' => '']))->toHaveKey('es');
});

test('a value that is not an array is rejected', function (mixed $value) {
    expect(TranslatedText::errors($value))->toHaveKey('');
})->with(['string' => 'hello', 'integer' => 5, 'null' => null]);

test('assert names the attribute and locale in the error', function () {
    try {
        TranslatedText::assert('meaning', ['es' => 'buscar']);
        $this->fail('Expected a ValidationException.');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('meaning.en');
    }

    try {
        TranslatedText::assert('theory', ['en' => 'x', 'fr' => 'y']);
        $this->fail('Expected a ValidationException.');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('theory.fr');
    }
});

test('assert passes for a valid value', function () {
    TranslatedText::assert('meaning', ['en' => 'to look for', 'es' => 'buscar']);

    expect(true)->toBeTrue();
});

test('it works as a validation rule', function () {
    $passes = Validator::make(['meaning' => ['en' => 'a', 'es' => 'b']], ['meaning' => [new TranslatedText]]);
    $fails = Validator::make(['meaning' => ['es' => 'b']], ['meaning' => [new TranslatedText]]);

    expect($passes->passes())->toBeTrue()
        ->and($fails->fails())->toBeTrue()
        ->and($fails->errors()->first('meaning'))->toContain('English');
});
