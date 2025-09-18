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
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'seller/*', 'user/*', 'public/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => env('APP_ENV') === 'local' ? ['*'] : [
        'http://localhost:3000',
        'http://localhost:3001',
        'http://localhost:3003',
        'http://127.0.0.1:3000',
        'http://127.0.0.1:3001',
        'http://127.0.0.1:3003',
        'https://kaclira.com',
        'https://www.kaclira.com',
        'https://admin.kaclira.com',
        'https://seller.kaclira.com',
        // Add the Nuxt dev server port if different
        'http://localhost:24678',
    ],

    'allowed_origins_patterns' => [
        '/^https:\/\/.*\.kaclira\.com$/',
        '/^https:\/\/kaclira-.*\.netlify\.app$/',
        '/^https:\/\/.*\.vercel\.app$/',
    ],

    'allowed_headers' => [
        'Accept',
        'Authorization',
        'Content-Type',
        'X-Requested-With',
        'X-API-Key',
        'X-API-Version',
        'X-Request-ID',
        'Origin',
        'Cache-Control',
        'Pragma',
    ],

    'exposed_headers' => [
        'X-RateLimit-Limit',
        'X-RateLimit-Remaining',
        'X-RateLimit-Reset',
        'X-RateLimit-User-Type',
        'X-API-Version',
        'X-API-Latest-Version',
        'X-API-Supported-Versions',
        'X-API-Deprecation-Warning',
        'X-API-Key-Usage',
        'X-Request-ID',
    ],

    'max_age' => 86400, // 24 hours

    'supports_credentials' => true,

];
