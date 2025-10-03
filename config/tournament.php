<?php

return [
    // System fee percentage for tournaments (deducted from prize pool)
    'system_fee_percentage' => env('TOURNAMENT_SYSTEM_FEE', 10), // 10% default

    // Minimum participants required for tournament
    'minimum_participants' => env('TOURNAMENT_MIN_PARTICIPANTS', 2),

    // Maximum participants allowed
    'maximum_participants' => env('TOURNAMENT_MAX_PARTICIPANTS', 1000),

    // Tournament status options
    'statuses' => [
        'open' => 'open',
        'close' => 'close',
    ],
];
