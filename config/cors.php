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
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'logout'],
    'allowed_methods' => ['*'],               // OPTIONS/GET/POST/PUT/PATCH/DELETE

    // Lee los orígenes permitidos desde .env para facilitar cambios sin tocar código
    'allowed_origins' => array_filter(explode(',', env('CORS_ALLOWED_ORIGINS', ''))),

    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => ['*'],
    'max_age' => 86400,                       // Cache de preflight por 24 horas
    'supports_credentials' => true,           // Permite envío de cookies/credenciales
];