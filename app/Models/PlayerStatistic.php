<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlayerStatistic extends Model
{
    protected $fillable = [
        'player_id',
        'fixture_id',
        'team_id',
        'match_date',
        'assists',
        'yellow_cards',
        'shots_on_target',
        'did_play',
        'is_injured',
        'minutes',
        'rating',
        'captain',
        'substitute',
        'shots_total',
        'goals_total',
        'offsides',
        'goals_conceded',
        'goals_assists',
        'goals_saves',
        'passes_total',
        'position',
        'tackles_total',
        'number'
    ];
    public function getPointsAttribute()
    {
        // Don't award points if player didn't play or was injured
        if (!$this->did_play || $this->is_injured) {
            return 0;
        }

        return ($this->goals_total ?? 0) * config('point.goal') +
            ($this->assists ?? 0) * config('point.assist') +
            ($this->shots_total ?? 0) * config('point.shot') +
            ($this->shots_on_target ?? 0) * config('point.shot_on_target') +
            ($this->yellow_cards ?? 0) * config('point.yellow_card');
    }
}
