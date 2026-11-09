<?php

use App\Models\User;
use Illuminate\Support\Str;

/*
 * learner_ref is the opaque identifier Parlora Tutor receives instead of any
 * personal data: a random UUID v4 (v7 would leak the sign-up time).
 */

test('every user gets a unique UUID v4 learner_ref', function () {
    $users = User::factory()->count(3)->create();

    $users->each(function (User $user) {
        expect(Str::isUuid($user->learner_ref, 4))->toBeTrue();
    });

    expect($users->pluck('learner_ref')->unique())->toHaveCount(3);
});

test('a registered learner gets a learner_ref', function () {
    $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    expect(Str::isUuid(User::where('email', 'test@example.com')->value('learner_ref'), 4))->toBeTrue();
});

test('learner_ref keeps its value when the email changes', function () {
    $user = User::factory()->create();
    $learnerRef = $user->learner_ref;

    $user->update(['email' => 'new@example.com']);

    expect($user->refresh()->learner_ref)->toBe($learnerRef);
});

test('learner_ref cannot be changed once set', function () {
    $user = User::factory()->create();

    $user->learner_ref = (string) Str::uuid();

    expect(fn () => $user->save())->toThrow(LogicException::class);
});
