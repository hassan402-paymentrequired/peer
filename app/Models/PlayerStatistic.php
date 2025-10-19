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
        'number',
        'clean_sheet',
        'shots_on_goal',
        'red_cards'
    ];
    public function getPointsAttribute()
    {
        if (!$this->did_play || $this->is_injured) {
            return 0;
        }

        $points = 0;

        // Goals (most important)
        $points += ($this->goals_total ?? 0) * config('point.goal', 6);

        // Assists
        $points += ($this->goals_assists ?? 0) * config('point.assist', 4);

        // Shots
        $points += ($this->shots_total ?? 0) * config('point.shot', 1);

        // Shots on target (bonus for accuracy)
        $points += ($this->shots_on_target ?? 0) * config('point.shot_on_target', 2);


        // Shots on goal (bonus for accuracy)
        $points += ($this->shots_on_goal ?? 0) * config('point.shot_on_goal', 2);

        // Yellow cards (penalty)
        $points += ($this->yellow_cards ?? 0) * config('point.yellow_card', -1);

         // red cards (penalty)
        $points += ($this->red_cards ?? 0) * config('point.red_card', -5);

        // Goalkeeper saves (if applicable)
        if ($this->position === 'G' && $this->goals_saves > 0) {
            $points += ($this->goals_saves ?? 0) * config('point.save', 1);
        }

         if ($this->position === 'D' && $this->goals_total > 0) {
            $points += ($this->goals_saves ?? 0) * config('point.save', 1);
        }

        // Clean sheet bonus for goalkeepers and defenders
        if (in_array($this->position, ['G', 'D']) && ($this->goals_conceded ?? 0) === 0 && $this->minutes >= 65) {
            if ($this->position === 'G') {
                $cal = config('point.clean_sheet_goalkeeper', 15);
                $points += $cal;
                $this->clean_sheet = $cal;
                $this->save();
            } else if ($this->position === 'D') {
                $cal = config('point.clean_sheet_defender', 15);
                $points += $cal;
                $this->clean_sheet = $cal;
                $this->save();
            }
        }

        return $points;
    }
}
