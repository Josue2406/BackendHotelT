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
    'allowed_methods' => ['*'],

    // Lee los orígenes permitidos desde .env
    // IMPORTANTE: No se puede usar '*' con credentials:true
    'allowed_origins' => ['https://lanaku-hotel.onrender.com'],

    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 86400,
    'supports_credentials' => true,
];