<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | This is a first-party Inertia SPA served from the same origin as its API —
    | there is no legitimate cross-origin caller. Scoped to api/* only (the
    | Razorpay webhook and any future token-authenticated endpoints) and closed
    | otherwise; the web/* Inertia routes never need CORS at all.
    |
    */

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
