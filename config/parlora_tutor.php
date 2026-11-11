<?php

/*
|--------------------------------------------------------------------------
| Parlora Tutor
|--------------------------------------------------------------------------
|
| The voice tutor Hub calls through its HTTP API (Phase 6). Nothing reads
| these values yet; they fix the names and keep the integration off until
| it is configured. With `enabled` false, Hub makes no request to Tutor.
|
| `api_key` must equal Tutor's PARLORA_API_KEY and `webhook_secret` its
| PARLORA_WEBHOOK_SECRET.
|
*/

return [

    'enabled' => (bool) env('PARLORA_TUTOR_ENABLED', false),

    // Base URL of Tutor's API: its service on the shared parlora-net network
    // in Docker, or http://127.0.0.1:8700 (Tutor's default host port).
    'url' => env('PARLORA_TUTOR_URL') ?: null,

    'api_key' => env('PARLORA_TUTOR_API_KEY') ?: null,

    'webhook_secret' => env('PARLORA_TUTOR_WEBHOOK_SECRET') ?: null,

];
