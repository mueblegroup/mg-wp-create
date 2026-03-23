<?php

return [

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
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

    'hitpay' => [
        'api_key' => env('HITPAY_API_KEY'),
        'salt' => env('HITPAY_SALT'),
        'webhook_salt' => env('HITPAY_WEBHOOK_SALT'),
        'base_url' => rtrim(env('HITPAY_BASE_URL', 'https://api.sandbox.hit-pay.com/v1'), '/'),
        'currency' => env('HITPAY_CURRENCY', 'MYR'),
        'webhook_url' => env('HITPAY_WEBHOOK_URL'),
        'success_url' => env('HITPAY_SUCCESS_URL'),
        'cancel_url' => env('HITPAY_CANCEL_URL'),
    ],

    'billing' => [
        'grace_days' => (int) env('BILLING_GRACE_DAYS', 3),
        'reminder_days' => (int) env('BILLING_REMINDER_DAYS', 3),
        'retry_days' => (int) env('BILLING_RETRY_DAYS', 1),
    ],

    'hestia' => [
        'host' => env('REMOTE_HOST'),
        'port' => env('REMOTE_PORT', 22),
        'user' => env('REMOTE_USERNAME'),
        'password' => env('REMOTE_PASSWORD'),
        'private_key_path' => env('REMOTE_PRIVATE_KEY_PATH'),
        'timeout' => env('REMOTE_CONNECT_TIMEOUT', 20),
    ],
];