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

    'payway' => [
        'merchant_id' => env('ABA_PAYWAY_MERCHANT_ID'),
        'api_key' => env('ABA_PAYWAY_API_KEY'),
        'purchase_url' => env('ABA_PAYWAY_PURCHASE_URL', 'https://checkout-sandbox.payway.com.kh/api/payment-gateway/v1/payments/purchase'),
        'check_url' => env('ABA_PAYWAY_CHECK_URL', 'https://checkout-sandbox.payway.com.kh/api/payment-gateway/v1/payments/check-transaction-2'),
        'return_url' => env('ABA_PAYWAY_RETURN_URL'),
        'frontend_url' => env('FRONTEND_URL', 'http://localhost:5173'),
        'currency' => env('ABA_PAYWAY_CURRENCY', 'USD'),
    ],

    'bakong' => [
        'account_id' => env('BAKONG_ACCOUNT_ID', 'sopheareaksa_pheak@bkrt'),
        'merchant_id' => env('BAKONG_MERCHANT_ID', env('BAKONG_ACCOUNT_ID', 'sopheareaksa_pheak@bkrt')),
        'acquiring_bank' => env('BAKONG_ACQUIRING_BANK', 'Bakong'),
        'account_type' => env('BAKONG_ACCOUNT_TYPE', 'individual'),
        'merchant_name' => env('BAKONG_MERCHANT_NAME', 'Sopheareaksa Pheak'),
        'merchant_city' => env('BAKONG_MERCHANT_CITY', 'Phnom Penh'),
        'api_url' => env('BAKONG_API_URL', 'https://api-bakong.nbc.gov.kh'),
        'api_token' => env('BAKONG_API_TOKEN', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJkYXRhIjp7ImlkIjoiYWM1YTBhMTczYjA5NDUxNyJ9LCJpYXQiOjE3ODcyMTMwNDEsImV4cCI6MTc5NDk4OTA0MX0.jgqpOE7oIQ52gim8NjX9NyKHHwG2Ff7wtzN5nNdgF24'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
