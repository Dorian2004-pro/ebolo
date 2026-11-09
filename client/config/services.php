<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'ebolo' => [
        'base_url' => env('EBOLO_API_URL', 'http://127.0.0.1:8003/api'),
        'client_role_id' => (int) env('EBOLO_CLIENT_ROLE_ID', 1),
        'artisan_role_id' => (int) env('EBOLO_ARTISAN_ROLE_ID', 2),
        'salon_role_id' => (int) env('EBOLO_SALON_ROLE_ID', 4),
        'provider_url' => env('EBOLO_PROVIDER_APP_URL', 'http://127.0.0.1:8004'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
