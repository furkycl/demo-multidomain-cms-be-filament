<?php

declare(strict_types=1);

return [
    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Lead Routing — CRM endpoints
    |--------------------------------------------------------------------------
    | Locale → CRM mapping config/locales.php'de (crm_target field).
    | Buradaki anahtarlar: omnigos, linguland.
    | Her CRM için endpoint + auth — env'den alınır, varsayılanlar boş.
    | Boşken backend lead'i sadece DB'ye yazar, dış POST atmaz (demo modu).
    */
    'crm' => [
        'omnigos' => [
            'endpoint' => env('OMNIGOS_LEAD_ENDPOINT'),
            'api_key' => env('OMNIGOS_API_KEY'),
            'timeout' => (int) env('OMNIGOS_TIMEOUT', 5),
        ],
        'linguland' => [
            'endpoint' => env('LINGULAND_LEAD_ENDPOINT'),
            'api_key' => env('LINGULAND_API_KEY'),
            'timeout' => (int) env('LINGULAND_TIMEOUT', 5),
        ],
    ],

    'revalidate' => [
        'timeout' => (int) env('FRONTEND_REVALIDATE_TIMEOUT', 5),
    ],
];
