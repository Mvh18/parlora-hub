<?php

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\Fixtures\Models\ContentStub;

beforeEach(function () {
    Schema::create('content_stubs', function ($table) {
        $table->id();
        $table->string('key', 120)->unique();
        $table->timestamp('archived_at')->nullable();
        $table->timestamps();
    });
});

test('well-formed keys are accepted', function (string $key) {
    expect(ContentStub::create(['key' => $key])->key)->toBe($key);
})->with([
    'a1',
    'a1.u03',
    'a1.u03.l02.ex05',
    'exam.a1.reading.q07',
    'a1-placement.q1',
    '0',
]);

test('malformed keys are rejected with an error naming key', function (?string $key) {
    try {
        ContentStub::create(['key' => $key]);
        $this->fail('Expected a ValidationException.');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('key');
    }

    expect(ContentStub::count())->toBe(0);
})->with([
    'spaces and capitals' => 'A1 Unit 1',
    'uppercase' => 'A1.U01',
    'double dot' => 'a1..u01',
    'leading dot' => '.a1',
    'trailing dot' => 'a1.',
    'underscore' => 'a1_u01',
    'empty' => '',
    'null' => null,
    'too long' => str_repeat('a', 121),
]);

test('a key of exactly 120 characters is accepted', function () {
    expect(ContentStub::create(['key' => str_repeat('a', 120)])->exists)->toBeTrue();
});

test('a duplicate key is rejected by the database', function () {
    ContentStub::create(['key' => 'a1.u01.l01']);

    expect(fn () => ContentStub::create(['key' => 'a1.u01.l01']))->toThrow(QueryException::class);
});

test('the key is immutable', function () {
    $stub = ContentStub::create(['key' => 'a1.u01']);

    $stub->key = 'a1.u02';

    expect(fn () => $stub->save())->toThrow(LogicException::class, 'immutable');
    expect($stub->fresh()->key)->toBe('a1.u01');
});

test('changing the key to a malformed one is still an immutability error', function () {
    $stub = ContentStub::create(['key' => 'a1.u01']);

    $stub->key = 'A1 Unit 2';

    expect(fn () => $stub->save())->toThrow(LogicException::class);
});

test('saving other attributes leaves the key alone', function () {
    $stub = ContentStub::create(['key' => 'a1.u01']);

    $stub->touch();
    $stub->update(['archived_at' => now()]);

    expect($stub->fresh()->key)->toBe('a1.u01');
});
