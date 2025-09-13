<?php

return [
    'enabled' => env('PAYSTACK_ENABLED', true),
    'base_url' => env('PAYSTACK_BASE_URL', 'https://api.paystack.co/'),
    'secret_key' => env('PAYSTACK_SECRET_KEY', null),
    'public_key' => env('PAYSTACK_PUBLIC_KEY', null),
    'webhook_secret' => env('PAYSTACK_WEBHOOK_SECRET', null),
    'currency' => env('PAYSTACK_CURRENCY', 'NGN'),
]; 
