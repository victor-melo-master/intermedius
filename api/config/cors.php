<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Aquí se configuran los orígenes permitidos para peticiones cross-origin.
    | Incluye los dominios de desarrollo de Flutter web.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:3000',
        'http://localhost:8080',
        'http://localhost:5000',
        'http://127.0.0.1:3000',
        'http://127.0.0.1:8080',
        'https://admin.intermediusg.com',
        'https://app.intermediusg.com',
        'https://intermediusg.com',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
