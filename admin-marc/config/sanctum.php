<?php

return [
    // L'API d'administration utilise des jetons personnels (Bearer), pas de SPA par cookie.
    'stateful' => [],

    'guard' => [],

    // Durée de vie des jetons, en minutes (24 h — identique au JWT du projet d'origine).
    'expiration' => 60 * 24,

    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),

    'middleware' => [
        'authenticate_session' => Laravel\Sanctum\Http\Middleware\AuthenticateSession::class,
        'encrypt_cookies' => Illuminate\Cookie\Middleware\EncryptCookies::class,
        'validate_csrf_token' => Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
    ],
];
