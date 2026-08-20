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

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    | Africa's Talking SMS gateway. `username` is "sandbox" on the test account.
    | Leave AT_API_KEY empty to keep SMS disabled (the dispatcher then reports the
    | channel as unconfigured instead of pretending to send).
    */
    'africastalking' => [
        'username' => env('AT_USERNAME', 'sandbox'),
        'api_key' => env('AT_API_KEY'),
        'sender_id' => env('AT_SENDER_ID'), // optional short code / alphanumeric sender
        'sandbox' => env('AT_SANDBOX', true),
    ],

];
