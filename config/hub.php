<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Registration
    |--------------------------------------------------------------------------
    |
    | When false, the registration routes are not registered (404) and the
    | sign-up links are hidden. Turn it off once the owner's account exists.
    |
    */

    'registration_enabled' => (bool) env('HUB_REGISTRATION_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Content locales
    |--------------------------------------------------------------------------
    |
    | Locales a translatable content value (glosses, explanations, rubric
    | descriptions) may carry. English is always required; the interface
    | itself stays English-only.
    |
    */

    'content_locales' => ['en', 'es'],

    /*
    |--------------------------------------------------------------------------
    | Content files
    |--------------------------------------------------------------------------
    |
    | Root folder of the versioned course files read by content:validate and
    | content:import. A relative value is resolved against base_path(). The
    | files carry every solution, so the real course can live outside this
    | repository by pointing HUB_CONTENT_PATH somewhere else.
    |
    */

    'content_path' => (static function (): string {
        $path = (string) env('HUB_CONTENT_PATH', '');

        if ($path === '') {
            return base_path('content');
        }

        $absolute = str_starts_with($path, '/')
            || str_starts_with($path, '\\')
            || preg_match('~^[A-Za-z]:[\\\\/]~', $path) === 1;

        return $absolute ? $path : base_path($path);
    })(),

];
