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
        'key' => env('POSTMARK_API_KEY'),
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

    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
        'api_version' => env('ANTHROPIC_API_VERSION', '2023-06-01'),
        'model' => env('ANTHROPIC_MODEL', 'claude-haiku-4-5-20251001'),
        'repertoire_search_model' => env('ANTHROPIC_REPERTOIRE_SEARCH_MODEL', 'claude-sonnet-4-6'),
        'repertoire_search_max_web_searches' => env('ANTHROPIC_REPERTOIRE_SEARCH_MAX_WEB_SEARCHES', 6),
    ],

    // Defaults are Cloudflare's published "always passes" test keys — safe for local dev,
    // must be overridden with real keys in production. https://developers.cloudflare.com/turnstile/troubleshooting/testing/
    'turnstile' => [
        'site_key' => env('TURNSTILE_SITE_KEY', '1x00000000000000000000AA'),
        'secret_key' => env('TURNSTILE_SECRET_KEY', '1x0000000000000000000000000000000AA'),
    ],

    'repertoire_search' => [
        'guest_query_limit' => env('REPERTOIRE_GUEST_QUERY_LIMIT', 5),
        'dashboard_stale_query_limit' => env('REPERTOIRE_DASHBOARD_STALE_QUERY_LIMIT', 5),
    ],

];
