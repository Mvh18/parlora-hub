<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia;

/*
 * Home is the authenticated landing page; the kit's Dashboard no longer exists.
 */

test('guests are redirected from home to the login page', function () {
    $this->get(route('home'))->assertRedirect(route('login'));
});

test('authenticated learners can visit home', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('home'));
});

test('guests see the landing page at the root', function () {
    $this->get('/')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->component('welcome'));
});

test('authenticated learners are redirected from the root to home', function () {
    $this->actingAs(User::factory()->create())
        ->get('/')
        ->assertRedirect(route('home', absolute: false));
});

test('fortify sends learners to home after signing in', function () {
    expect(config('fortify.home'))->toBe('/home');
});

test('the dashboard route is gone', function () {
    expect(Route::has('dashboard'))->toBeFalse();

    $this->actingAs(User::factory()->create())->get('/dashboard')->assertNotFound();
});

test('home shares the translations it renders', function () {
    $page = $this->actingAs(User::factory()->create())->get(route('home'))->viewData('page');

    expect($page['props']['translations']['home']['greeting'])
        ->toHaveKeys(['morning', 'afternoon', 'evening'])
        ->and($page['props']['translations']['nav']['home'])->toBe('Home');
});
