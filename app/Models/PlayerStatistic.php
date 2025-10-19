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
        'shots_on_goal',
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
        'red_cards',
        'total_point'
    ];
    public function getPointsAttribute()
    {
        if (!$this->did_play || $this->is_injured) {
            return 0;
        }

        $points = 0;

        // Goals (most important)
        $points += ($this->goals_total ?? 0) * config('point.goal', 13);

        // Assists
        $points += ($this->goals_assists ?? 0) * config('point.assist', 7);

        // Shots total
        $points += ($this->shots_total ?? 0) * config('point.shots_total', 2);

        // Shots on target (bonus for accuracy)
        $points += ($this->shots_on_target ?? 0) * config('point.shot_on_target', 1);

        // Shots on goal (if different from shots on target)
        if (isset($this->shots_on_goal) && $this->shots_on_goal !== $this->shots_on_target) {
            $points += ($this->shots_on_goal ?? 0) * config('point.shot_on_goal', 1);
        }

        // Yellow cards (penalty)
        $points += ($this->yellow_cards ?? 0) * config('point.yellow_card', -1);

        // Red cards (penalty)
        $points += ($this->red_cards ?? 0) * config('point.red_card', -5);

        // Goalkeeper and Defender Clean Sheet Logic
        if (in_array($this->position, ['G', 'D']) && ($this->minutes ?? 0) >= 65) {
            $goalsConceeded = $this->goals_conceded ?? 0;

            if ($this->position === 'G') {
                // GOALKEEPER LOGIC
                $goalsSaved = $this->goals_saves ?? 0;

                if ($goalsConceeded === 0) {
                    // Clean sheet: 15 points + (saves * 3 points each)
                    $cleanSheetPoints = config('point.clean_sheet_goalkeeper', 15);
                    $savePoints = $goalsSaved * config('point.goals_saves', 3);
                    $totalCleanSheetPoints = $cleanSheetPoints + $savePoints;

                    $points += $totalCleanSheetPoints;


                    $this->clean_sheet = $totalCleanSheetPoints;
                    $this->save();
                } else {
                    // Conceded goals: lose clean sheet bonus, only get save points
                    $savePoints = $goalsSaved * config('point.goals_saves', 3);
                    $points += $savePoints;
                    $this->clean_sheet = 0;
                    $this->save();
                }
            } else if ($this->position === 'D') {
                // DEFENDER LOGIC
                if ($goalsConceeded === 0) {
                    // Clean sheet: 10 points
                    $cleanSheetPoints = config('point.clean_sheet_defender', 10);
                    $points += $cleanSheetPoints;

                    
                        $this->clean_sheet = $cleanSheetPoints;
                        $this->save();
                    
                } else {
                    // Conceded goals: no clean sheet bonus
                        $this->clean_sheet = 0;
                        $this->save();
                }
            }
        }

        $total =  max(0, $points); 

        $this->total_point = $total;
        $this->save();

        return $total;
    }
}
