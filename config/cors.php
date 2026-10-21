<?php

return [

    'paths' => explode(',', env('CORS_PATHS', 'api/*')),

    'allowed_methods' => explode(',', env('CORS_ALLOWED_METHODS', 'GET,POST,PUT,PATCH,DELETE,OPTIONS')),

    'allowed_origins' => env('CORS_ALLOWED_ORIGINS') === '*' ? ['*'] : explode(',', env('CORS_ALLOWED_ORIGINS', '')),

    'allowed_origins_patterns' => env('CORS_ALLOWED_ORIGINS_PATTERNS') ? explode(',', env('CORS_ALLOWED_ORIGINS_PATTERNS')) : [],

    'allowed_headers' => env('CORS_ALLOWED_HEADERS') === '*' ? ['*'] : explode(',', env('CORS_ALLOWED_HEADERS', 'Content-Type,Authorization')),

    'exposed_headers' => explode(',', env('CORS_EXPOSED_HEADERS', '')),

    'max_age' => 0,

    'supports_credentials' => true,

];
