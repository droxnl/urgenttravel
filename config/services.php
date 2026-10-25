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

    'openai' => [
        'enabled' => env('OPENAI_ENABLED', false),
        'key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com'),
    ],

    'hostinger_mail' => [
        'token' => env('HOSTINGER_MAIL_API_TOKEN'),
        'mailbox_resource_id' => env('HOSTINGER_MAILBOX_RESOURCE_ID'),
        'base_url' => env('HOSTINGER_MAIL_API_BASE_URL', 'https://api.mail.hostinger.com/api/v1'),
    ],

    'nominatim' => [
        'base_url' => env('BOOKING_GEOCODING_URL', 'https://nominatim.openstreetmap.org/search'),
        'user_agent' => env('BOOKING_GEOCODING_USER_AGENT', 'UrgentTravelServices/1.0 (info@urgent-travelservices.nl)'),
    ],

    'photon' => [
        'base_url' => env('BOOKING_ADDRESS_SEARCH_URL', 'https://photon.komoot.io/api'),
        'user_agent' => env('BOOKING_GEOCODING_USER_AGENT', 'UrgentTravelServices/1.0 (info@urgent-travelservices.nl)'),
    ],

    'osrm' => [
        'base_url' => env('BOOKING_ROUTE_URL', 'https://router.project-osrm.org'),
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

];
