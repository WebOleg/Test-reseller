<?php

return [
    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'reseller_api' => [
        'login' => env('RESELLER_API_LOGIN'),
        'password' => env('RESELLER_API_PASSWORD'),
        'timeout' => env('RESELLER_API_TIMEOUT', 30),
    ],

    'webhook' => [
        'secret' => env('WEBHOOK_SECRET'),
    ],
];
