<?php

return [

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
    ],

    'guards' => [
        'web' => [
            'driver'   => 'session',
            'provider' => 'users',
        ],

        // Guard para tu API usando Sanctum
        'api' => [
            'driver'   => 'sanctum',
            'provider' => 'users',
        ],

        'cliente' => [
        'driver' => 'sanctum',   // para API token
        'provider' => 'clientes',
    ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model'  => App\Models\usuario\User::class,
        ],
        // Si quisieras provider por DB directa:
        // 'users' => ['driver' => 'database', 'table' => 'users'],
        'clientes' => [
        'driver' => 'eloquent',
        'model'  => App\Models\cliente\Cliente::class,
    ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 60,
            'throttle' => 60,
        ],
         'clientes' => [
        'provider' => 'clientes',
        'table' => 'password_reset_tokens', // Laravel 10+
        'expire' => 60,  // minutos
        'throttle' => 60,
    ],
    ],

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),
];
