<?php

return [
    'name' => env('APP_NAME', 'EBOLO Admin'),
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),
    'timezone' => env('APP_TIMEZONE', 'Africa/Douala'),
    'locale' => env('APP_LOCALE', 'fr'),
    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'fr'),
    'faker_locale' => env('APP_FAKER_LOCALE', 'fr_FR'),
    // --- Paramètres EBOLO (utilisés par les seeders) ---------------------------------------
    'admin_email' => env('ADMIN_EMAIL', 'admin@ebolo.com'),
    'admin_password' => env('ADMIN_PASSWORD', 'admin123'),
    'seed_demo_data' => (bool) env('SEED_DEMO_DATA', true),

    'cipher' => 'AES-256-CBC',
    'key' => env('APP_KEY'),
    'previous_keys' => [
        ...array_filter(explode(',', (string) env('APP_PREVIOUS_KEYS', ''))),
    ],
    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],
];
