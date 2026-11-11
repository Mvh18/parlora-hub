<?php

use Illuminate\Support\Facades\Route;

// Guests see the landing page; the guest middleware sends signed-in learners
// to Home.
Route::inertia('/', 'welcome')->middleware('guest')->name('welcome');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('home', 'home')->name('home');
});

require __DIR__.'/settings.php';
