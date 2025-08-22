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

    // Paths that CORS should be applied to
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    // HTTP methods allowed for CORS requests
    'allowed_methods' => ['*'],

    // Origins allowed to make requests
    // In production, replace with your actual frontend domain
    'allowed_origins' => [
        'http://localhost:3000',     // React development server
        'http://127.0.0.1:3000',    // Alternative localhost
        'http://localhost:3001',     // Alternative port
    ],

    // Regex patterns for allowed origins
    'allowed_origins_patterns' => [],

    // Headers that are allowed in the actual request
    'allowed_headers' => ['*'],

    // Headers that are exposed to the client
    'exposed_headers' => [],

    // Maximum age for preflight requests in seconds
    'max_age' => 0,

    // Whether credentials (cookies, authorization headers) are supported
    'supports_credentials' => false,

];