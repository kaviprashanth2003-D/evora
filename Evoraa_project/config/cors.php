<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | Vulnerability #2 Fix: Never use wildcard allowed_origins ("*").
    | We restrict origins strictly based on an environment whitelist.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],

    'allowed_origins' => explode(',', env('ALLOWED_ORIGINS', 'https://evoraa-clothing.com,https://www.evoraa-clothing.com,http://localhost:3000')),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Content-Type', 'X-Requested-With', 'Authorization', 'Accept', 'X-CSRF-Token'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
