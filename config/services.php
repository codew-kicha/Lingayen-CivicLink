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

    // AI reading of activity sign-in sheets (App\Services\SheetReader). Off until both are set; the
    // free checks run either way. Costs about USD 0.0004 a sheet with Claude Haiku 5.5. Needs the
    // office's Data Privacy Act approval first (docs/CLIENT_CONFIRMATIONS.md).
    'anthropic' => [
        'key' => env('ANTHROPIC_API_KEY'),
        'sheet_reader' => (bool) env('AI_SHEET_READER', false),
        'model' => env('AI_SHEET_MODEL', 'claude-haiku-5-5'),
    ],

];
