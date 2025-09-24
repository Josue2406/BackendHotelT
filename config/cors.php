<?php

// return [
//     'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'logout'],
//     'allowed_methods' => ['*'],

//     // lee de .env para no tocar código al cambiar dominios
//     'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', '')),

//     'allowed_origins_patterns' => [],
//     'allowed_headers' => ['*'],
//     'exposed_headers' => [],
//     'max_age' => 86400,
//     'supports_credentials' => (bool) env('CORS_SUPPORTS_CREDENTIALS', false),
// ];
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],               // OPTIONS/GET/POST/etc
    'allowed_origins' => [
        'http://localhost:5173',
        'http://127.0.0.1:5173',
        'https://una-hotel-system.vercel.app/',
    ],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,         // déjalo en false si NO envías cookies
];