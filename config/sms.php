<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default SMS Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default SMS driver that will be used to send
    | SMS messages. You may set this to any of the drivers defined in the
    | "drivers" array below.
    |
    */

    'default' => env('SMS_DRIVER', 'termii'),

    /*
    |--------------------------------------------------------------------------
    | SMS Drivers
    |--------------------------------------------------------------------------
    |
    | Here you may configure the SMS drivers for your application. You may
    | even configure multiple drivers for the same service to send different
    | types of messages.
    |
    */

    'drivers' => [
        'termii' => [
            'api_url' => env('TERMII_API_URL', 'https://v3.api.termii.com/api/sms/send'),
            'api_key' => env('TERMII_API_KEY'),
            'sender_id' => env('TERMII_SENDER_ID', 'Starpick'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Termii Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration specific to Termii SMS service
    |
    */

    'termii' => [
        'api_url' => env('TERMII_API_URL', 'https://v3.api.termii.com/api/sms/send'),
        'api_key' => env('TERMII_API_KEY'),
        'sender_id' => env('TERMII_SENDER_ID', 'Starpick'),
        'channel' => env('TERMII_CHANNEL', 'generic'), // generic, dnd, whatsapp
    ],
];
