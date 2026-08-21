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

    'facebook' => [
        'client_id' => env('WHATSAPP_APP_ID'),
        'client_secret' => env('WHATSAPP_APP_SECRET'),
        'redirect' => 'https://postzy.webzy.co.in/auth/facebook/login/callback',
        'app_id' => env('FACEBOOK_APP_ID'),
        'app_secret' => env('FACEBOOK_APP_SECRET'),
        'redirect_uri' => env('FACEBOOK_REDIRECT_URI'),
    ],

    'whatsapp' => [
        'app_id' => env('WHATSAPP_APP_ID'),
        'app_secret' => env('WHATSAPP_APP_SECRET'),
        'client_token' => env('WHATSAPP_CLIENT_TOKEN'),
        'config_id' => env('WHATSAPP_CONFIG_ID'),
    ],


    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID', env('GOOGLE_DRIVE_CLIENT_ID')),
        'client_secret' => env('GOOGLE_CLIENT_SECRET', env('GOOGLE_DRIVE_CLIENT_SECRET')),
        'redirect_uri' => env('GOOGLE_REDIRECT_URI', env('GOOGLE_DRIVE_REDIRECT_URI')),
        'redirect' => env('GOOGLE_LOGIN_REDIRECT_URI', '/auth/google/callback'),
    ],

    'google_drive' => [
        'api_key' => env('GOOGLE_DRIVE_API_KEY'),
    ],


    'razorpay' => [
        'key_id' => env('RAZORPAY_KEY_ID'),
        'key_secret' => env('RAZORPAY_KEY_SECRET'),
        'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET'),
        'auto_create_plans' => env('RAZORPAY_AUTO_CREATE_PLANS', false),
    ],

];
