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

];
