<?php

use Illuminate\Support\Facades\Artisan;

test('artisan about runs', function () {
    expect(Artisan::call('about', ['--json' => true]))->toBe(0);

    $about = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

    expect($about)->toHaveKeys(['environment', 'drivers']);
});

test('the welcome page renders', function () {
    $this->get('/')->assertOk();
});
