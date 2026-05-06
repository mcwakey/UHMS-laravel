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

    /*
    |--------------------------------------------------------------------------
    | Insurance Verification — generic per-provider credential bundles
    |--------------------------------------------------------------------------
    | Each insurance provider with verification_driver = "api" picks a logical
    | credentials key (insurance_providers.verification_credentials_key). The
    | bundle is looked up by that key from this map. Adding a new provider
    | only requires adding a new entry below + the matching .env values; NO
    | code changes anywhere else.
    */
    'insurance_verification' => [
        // Example shape (do not enable unless real credentials are provisioned):
        // 'nhia' => [
        //     'token'    => env('INSURANCE_NHIA_API_TOKEN'),
        //     'username' => env('INSURANCE_NHIA_API_USERNAME'),
        //     'password' => env('INSURANCE_NHIA_API_PASSWORD'),
        //     'timeout'  => env('INSURANCE_NHIA_API_TIMEOUT', 10),
        // ],
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
