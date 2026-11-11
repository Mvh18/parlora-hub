<?php

use App\Models\User;
use Illuminate\Http\Client\StrayRequestException;
use Illuminate\Support\Facades\Http;

/*
 * Hub works with Parlora Tutor switched off: no test reaches the network,
 * so any request to Tutor would fail the suite.
 */

test('every test runs with stray HTTP requests prevented', function () {
    Http::get('http://127.0.0.1:8700/health');
})->throws(StrayRequestException::class);

test('stray requests stay prevented after the application is rebuilt', function () {
    $this->bootWithConfig(['hub.registration_enabled' => false]);

    Http::get('http://127.0.0.1:8700/health');
})->throws(StrayRequestException::class);

test('the learner pages work without parlora tutor', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('home'))->assertOk();
    $this->get(route('profile.edit'))->assertOk();

    Http::assertNothingSent();
});
