<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Origins are read from CORS_ALLOWED_ORIGINS (comma-separated) so each
    | environment can pin its own frontend hosts. `supports_credentials` is
    | true because the SPA uses Sanctum cookie auth, which forbids a wildcard
    | origin — list explicit origins instead.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_filter(
        explode(',', (string) env('CORS_ALLOWED_ORIGINS', 'http://localhost:5173'))
    ),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
