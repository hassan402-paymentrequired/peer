<?php

namespace App\Enum;

enum CacheKey: string
{
    case PLAYER = 'players';
    case TEAMS = 'teams';
    case MATCH = 'matches';
    case PEERS = 'peers';
    case RECENT_PEERS = 'recent_peer';
}
