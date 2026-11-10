<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

/*
 * hub.registration_enabled decides at boot whether Fortify registers the
 * registration routes, so the disabled case rebuilds the application.
 */

test('registration is enabled by default', function () {
    expect(config('hub.registration_enabled'))->toBeTrue();
});

test('the registration page is not found when registration is disabled', function () {
    $this->bootWithConfig(['hub.registration_enabled' => false]);

    expect(config('hub.registration_enabled'))->toBeFalse();

    $this->get('/register')->assertNotFound();
});

test('the registration form cannot be posted when registration is disabled', function () {
    $this->bootWithConfig(['hub.registration_enabled' => false]);

    $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertNotFound();
});

test('the login page hides the sign-up link when registration is disabled', function () {
    $this->bootWithConfig(['hub.registration_enabled' => false]);

    $this->get(route('login'))->assertInertia(
        fn (Assert $page) => $page->where('features.registration', false),
    );
});

test('the login page shows the sign-up link when registration is enabled', function () {
    $this->get(route('login'))->assertInertia(
        fn (Assert $page) => $page->where('features.registration', true),
    );
});

test('a visitor registers and lands on Home when registration is enabled', function () {
    $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
    expect(User::where('email', 'test@example.com')->exists())->toBeTrue();
});
