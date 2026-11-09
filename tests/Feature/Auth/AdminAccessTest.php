<?php

use App\Models\User;
use Illuminate\Support\Str;

/*
 * One admin flag guards every admin surface. It can only change through
 * `php artisan hub:make-admin`, and neither it nor learner_ref ever reaches
 * the browser.
 */

test('new users are not admins', function () {
    expect(User::factory()->create()->refresh()->is_admin)->toBeFalse();
});

test('registration ignores an is_admin field', function () {
    $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'is_admin' => 1,
    ]);

    expect(User::where('email', 'test@example.com')->firstOrFail()->is_admin)->toBeFalse();
});

test('a profile update ignores is_admin and learner_ref fields', function () {
    $user = User::factory()->create();
    $learnerRef = $user->learner_ref;

    $this->actingAs($user)->patch(route('profile.update'), [
        'name' => 'Updated Name',
        'email' => $user->email,
        'is_admin' => 1,
        'learner_ref' => (string) Str::uuid(),
    ]);

    $user->refresh();

    expect($user->name)->toBe('Updated Name')
        ->and($user->is_admin)->toBeFalse()
        ->and($user->learner_ref)->toBe($learnerRef);
});

test('Inertia props never contain is_admin or learner_ref', function () {
    $user = User::factory()->admin()->create();

    $page = $this->actingAs($user)->get(route('dashboard'))->assertOk()->viewData('page');

    $payload = (string) json_encode($page);

    expect($payload)->toContain('"auth"');

    expect($payload)
        ->not->toContain('"is_admin"')
        ->not->toContain('"learner_ref"')
        ->not->toContain($user->learner_ref);
});

test('hub:make-admin grants the admin flag', function () {
    $user = User::factory()->create();

    $this->artisan('hub:make-admin', ['email' => $user->email])
        ->expectsOutputToContain($user->email)
        ->assertSuccessful();

    expect($user->refresh()->is_admin)->toBeTrue();
});

test('hub:make-admin --revoke removes the admin flag', function () {
    $user = User::factory()->admin()->create();

    $this->artisan('hub:make-admin', ['email' => $user->email, '--revoke' => true])
        ->assertSuccessful();

    expect($user->refresh()->is_admin)->toBeFalse();
});

test('hub:make-admin fails for an unknown email', function () {
    $this->artisan('hub:make-admin', ['email' => 'nobody@example.com'])
        ->expectsOutputToContain('nobody@example.com')
        ->assertFailed();
});

test('a learner cannot open Horizon even when APP_ENV is local', function () {
    $this->app['env'] = 'local';

    $this->actingAs(User::factory()->create())->get('/horizon')->assertForbidden();
});

test('a guest cannot open Horizon even when APP_ENV is local', function () {
    $this->app['env'] = 'local';

    $this->get('/horizon')->assertForbidden();
});

test('an admin can open Horizon', function () {
    $this->actingAs(User::factory()->admin()->create())->get('/horizon')->assertOk();
});
