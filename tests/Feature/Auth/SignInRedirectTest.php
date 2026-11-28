<?php

use App\Models\User;
use Laravel\Fortify\Features;

/*
 * The sign-in forms submit through Inertia. A destination that is not an
 * Inertia page (the content panel, Horizon) must be reached with a full page
 * load, or Inertia shows its HTML in an error modal (#98).
 */

const INERTIA_HEADERS = ['X-Inertia' => 'true', 'X-Requested-With' => 'XMLHttpRequest'];

test('password login redirects to the panel with a full page load', function () {
    $admin = User::factory()->admin()->create();

    $this->get('/admin')->assertRedirect(route('login'));

    $this->withHeaders(INERTIA_HEADERS)
        ->post(route('login.store'), ['email' => $admin->email, 'password' => 'password'])
        ->assertStatus(409)
        ->assertHeader('X-Inertia-Location', url('/admin'));

    $this->assertAuthenticatedAs($admin);
});

test('two factor challenge redirects to the panel with a full page load', function () {
    $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

    $admin = User::factory()->admin()->withTwoFactor()->create();

    $this->get('/admin')->assertRedirect(route('login'));

    $this->withHeaders(INERTIA_HEADERS)
        ->post(route('login.store'), ['email' => $admin->email, 'password' => 'password'])
        ->assertRedirect(route('two-factor.login'));

    $this->withHeaders(INERTIA_HEADERS)
        ->post(route('two-factor.login.store'), ['recovery_code' => 'recovery-code-1'])
        ->assertStatus(409)
        ->assertHeader('X-Inertia-Location', url('/admin'));

    $this->assertAuthenticatedAs($admin);
});

test('password login redirects to horizon with a full page load', function () {
    $admin = User::factory()->admin()->create();

    $this->withSession(['url.intended' => url('/'.config('horizon.path').'/dashboard')])
        ->withHeaders(INERTIA_HEADERS)
        ->post(route('login.store'), ['email' => $admin->email, 'password' => 'password'])
        ->assertStatus(409)
        ->assertHeader('X-Inertia-Location', url('/'.config('horizon.path').'/dashboard'));
});

test('learner pages keep the normal redirect', function () {
    $user = User::factory()->create();

    $this->withHeaders(INERTIA_HEADERS)
        ->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(config('fortify.home'));
});

test('a path that only starts like the panel path keeps the normal redirect', function () {
    $user = User::factory()->create();

    $this->withSession(['url.intended' => url('/administration')])
        ->withHeaders(INERTIA_HEADERS)
        ->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(url('/administration'));
});

test('a login without inertia keeps the normal redirect to the panel', function () {
    $admin = User::factory()->admin()->create();

    $this->withSession(['url.intended' => url('/admin')])
        ->post(route('login.store'), ['email' => $admin->email, 'password' => 'password'])
        ->assertRedirect(url('/admin'));
});

test('json logins keep fortify json responses', function () {
    $user = User::factory()->create();

    $this->postJson(route('login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertOk()
        ->assertExactJson(['two_factor' => false]);
});

test('json two factor challenges keep the empty response', function () {
    $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

    $user = User::factory()->withTwoFactor()->create();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);

    $this->postJson(route('two-factor.login.store'), ['recovery_code' => 'recovery-code-1'])
        ->assertNoContent();
});
