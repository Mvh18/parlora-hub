<?php

use Illuminate\Support\Facades\Schema;
use Tests\Fixtures\Models\ContentStub;

beforeEach(function () {
    Schema::create('content_stubs', function ($table) {
        $table->id();
        $table->string('key', 120)->unique();
        $table->timestamp('archived_at')->nullable();
        $table->timestamps();
    });
});

test('rows are active until archived', function () {
    $stub = ContentStub::create(['key' => 'a1']);

    expect($stub->isArchived())->toBeFalse()
        ->and($stub->archived_at)->toBeNull();
});

test('archive stamps archived_at and persists it', function () {
    $stub = ContentStub::create(['key' => 'a1']);

    $stub->archive();

    expect($stub->isArchived())->toBeTrue()
        ->and($stub->fresh()->archived_at)->not->toBeNull();
});

test('the active scope hides archived rows without a global scope', function () {
    ContentStub::create(['key' => 'a1.ex01']);
    ContentStub::create(['key' => 'a1.ex02']);
    ContentStub::create(['key' => 'a1.ex03'])->archive();

    expect(ContentStub::query()->active()->count())->toBe(2)
        ->and(ContentStub::query()->count())->toBe(3)
        ->and(ContentStub::find(3))->not->toBeNull();
});

test('restore brings an archived row back', function () {
    $stub = ContentStub::create(['key' => 'a1'])->fresh();
    $stub->archive();

    $stub->restore();

    expect($stub->archived_at)->toBeNull()
        ->and($stub->fresh()->isArchived())->toBeFalse()
        ->and(ContentStub::query()->active()->count())->toBe(1);
});

test('archiving twice keeps the first timestamp', function () {
    $stub = ContentStub::create(['key' => 'a1']);

    $this->travelTo(now()->subDay());
    $stub->archive();
    $first = $stub->fresh()->archived_at;

    $this->travelBack();
    $stub->archive();

    expect($stub->fresh()->archived_at->equalTo($first))->toBeTrue();
});

test('archived_at is cast to a date', function () {
    $stub = ContentStub::create(['key' => 'a1']);
    $stub->archive();

    expect($stub->fresh()->archived_at)->toBeInstanceOf(DateTimeInterface::class);
});
