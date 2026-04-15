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

    'otp_transaction' => [
        'api_key'        => env('SHREE_SMS_API_KEY'),
        'sender_id'      => env('SHREE_SMS_SENDER'),
        'sms_type'       => env('SHREE_SMS_TYPE', 2),
        'entity_id'      => env('SHREE_SMS_ENTITY_ID'),
        'template_id'    => env('SHREE_SMS_TEMPLATE_ID'),
        'url'            => env('SHREE_SMS_URL'),
        'expiry_minutes' => env('SHREE_SMS_EXPIRY_MINUTES', 10),
    ],

    'firebase' => [
        'project_id'       => env('FIREBASE_PROJECT_ID'),
        'credentials_path' => env('FIREBASE_CREDENTIALS_PATH') ?: storage_path('app/firebase-service-account.json'),
    ],

    'onesignal' => [
        'app_id' => env('ONESIGNAL_APP_ID'),
        'rest_api_url' => env('ONESIGNAL_REST_API_URL', 'https://api.onesignal.com'),
        'rest_api_key' => env('ONESIGNAL_REST_API_KEY'),
        'guzzle_client_timeout' => env('ONESIGNAL_GUZZLE_CLIENT_TIMEOUT', 0),
    ],

];
