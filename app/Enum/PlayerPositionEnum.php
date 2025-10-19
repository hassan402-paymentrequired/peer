<?php

namespace App\Enum;

enum PlayerPositionEnum: string
{
    case GOALKEEPER = 'Goalkeeper';
    case DEFENDER = 'Defender';
    case MIDFIELDER = 'Midfielder';
    case FORWARD = 'Forward';
    case ATTACKER = 'Attacker';
}
