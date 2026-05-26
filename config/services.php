<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
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

    /*
    |--------------------------------------------------------------------------
    | Live Exchange Rates (open.er-api.com)
    |--------------------------------------------------------------------------
    | Free, no API key required. Base currency = EGP so the response gives us
    | the cost of 1 EGP in every other currency; we invert to get FX→EGP.
    | Set EXCHANGE_API_BASE_URL in .env to swap providers without code changes.
    */
    'exchange_rates_api' => [
        'base_url'   => env('EXCHANGE_API_BASE_URL', 'https://open.er-api.com/v6'),
        'api_key'    => env('EXCHANGE_API_KEY'),
        'timeout'    => env('EXCHANGE_API_TIMEOUT', 15),
        'currencies' => ['SAR', 'USD', 'EUR'],
    ],

];
