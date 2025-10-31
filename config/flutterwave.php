<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Flutterwave Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for Flutterwave payment gateway
    |
    */

    'public_key' => env('FLW_PUBLIC_KEY'),
    'secret_key' => env('FLW_SECRET_KEY'),
    'encryption_key' => env('FLW_ENCRYPTION_KEY'),
    'environment' => env('FLW_ENV', 'staging'), // staging or live
    'base_url' => env('FLW_ENV', 'staging') === 'live'
        ? 'https://api.flutterwave.com/v3'
        : 'https://developersandbox-api.flutterwave.com',
    'secret_hash' => env('FLW_SECRET_HASH'), // For webhook verification
    'currency' => env('FLW_CURRENCY', 'NGN'),
    'country' => env('FLW_COUNTRY', 'NG'),
];
