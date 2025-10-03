<?php

return [
    // System fee percentage for peer competitions (deducted from prize pool)
    'system_fee_percentage' => env('PEER_SYSTEM_FEE', 5), // 5% default (lower than tournaments)

    // Default sharing ratio options
    'sharing_ratios' => [
        'winner_takes_all' => 1,
        'divide_among_participants' => 2,
    ],

    // Peer status options
    'statuses' => [
        'open' => 'open',
        'closed' => 'closed',
        'finished' => 'finished',
    ],
];
