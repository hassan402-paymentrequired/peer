<?php
namespace App\Enum;

enum FixtureStatusEnum: string
{
    case NOT_STARTED = 'Not Started';
    case FIRST_HALF = 'First Half';
    case SECOND_HALF = 'Second Half';
    case HALFTIME = 'Halftime';
    case EXTRA_TIME = 'Extra Time';
    case PENALTY_IN_PROGRESS = 'Penalty In Progress';
    case IN_PROGRESS = 'In Progress';
    case MATCH_FINISHED = 'Match Finished';
    case PAUSED = 'Paused';
    case BREAK_TIME = 'Break Time';
    case IN_PLAY = 'In Play';
    case MATCH_CANCELLED = 'Match Cancelled';
    case MATCH_POSTPONED = 'Match Postponed';
    case MATCH_SUSPENDED = 'Match Suspended';
    case SECOND_HALF_STARTED = 'Second Half, 2nd Half Started';
    case KICK_OFF = 'First Half, Kick Off';
}