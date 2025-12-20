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
        'total_point',
        'fouls_committed'
    ];
    public static function calculatePoints(array $attributes): array
    {
        $points = 0;
        $cleanSheet = 0;

        // Check if player played
        $minutes = $attributes['minutes'] ?? 0;
        $didPlay = ($attributes['did_play'] ?? false) || $minutes > 0;
        $isInjured = $attributes['is_injured'] ?? false;

        if (!$didPlay || $isInjured) {
            return ['points' => 0, 'clean_sheet' => 0];
        }

        // Goals (most important)
        $points += ($attributes['goals_total'] ?? 0) * config('point.goal', 13);

        // Assists - Use 'goals_assists' as per existing logic
        $points += ($attributes['goals_assists'] ?? 0) * config('point.assist', 7);

        // Shots total
        $points += ($attributes['shots_total'] ?? 0) * config('point.shots_total', 2);

        // Shots on target (bonus for accuracy)
        $shotsOnTarget = $attributes['shots_on_target'] ?? 0;
        $points += $shotsOnTarget * config('point.shot_on_target', 1);

        // Shots on goal (if different from shots on target)
        $shotsOnGoal = $attributes['shots_on_goal'] ?? 0;
        if ($shotsOnGoal > 0 && $shotsOnGoal !== $shotsOnTarget) {
            $points += $shotsOnGoal * config('point.shot_on_goal', 1);
        }

        // Yellow cards (penalty)
        $points += ($attributes['yellow_cards'] ?? 0) * config('point.yellow_card', -1);

        // Red cards (penalty)
        $points += ($attributes['red_cards'] ?? 0) * config('point.red_card', -5);

        // Goalkeeper and Defender Clean Sheet Logic
        $position = $attributes['position'] ?? '';

        // Handle full position names from API just in case (though DB script showed abbreviations, simple safety)
        if (in_array($position, ['Goalkeeper', 'G', 'Defender', 'D']) && $minutes >= 60) {
            $goalsConceded = $attributes['goals_conceded'] ?? 0;
            $goalsSaved = $attributes['goals_saves'] ?? 0;

            if (in_array($position, ['G', 'Goalkeeper'])) {
                if ($goalsConceded === 0) {
                    // Clean sheet: 15 points + (saves * 3 points each)
                    $cleanSheetPoints = config('point.clean_sheet_goalkeeper', 30);
                    $savePoints = $goalsSaved * config('point.goals_saves', 3);

                    $totalCleanSheetPoints = $cleanSheetPoints + $savePoints;
                    $points += $totalCleanSheetPoints;
                    $cleanSheet = $cleanSheetPoints;
                } else {
                    // Conceded goals: lose clean sheet bonus, only get save points
                    $concedePoints = $goalsConceded * config('point.goals_conceded_goalkeeper', -2);
                    $savePoints = $goalsSaved * config('point.goals_saves', 3);
                    $points += $concedePoints;
                    $points += $savePoints;
                    $cleanSheet = 0;
                }
            } else if (in_array($position, ['D', 'Defender'])) {
                // DEFENDER LOGIC
                if ($goalsConceded === 0) {
                    // Clean sheet: 10 points
                    $cleanSheetPoints = config('point.clean_sheet_defender', 20);
                    $points += $cleanSheetPoints;
                    $cleanSheet = $cleanSheetPoints;
                } else {
                    $cleanSheet = 0;
                }
            }
        }

        // Tackles
        $points += ($attributes['tackles_total'] ?? 0) * config('point.tackle', 2);

        // Fouls committed (penalty)
        $points += ($attributes['fouls_committed'] ?? 0) * config('point.fouls_committed', -1);

        return [
            'points' =>  $points,
            'clean_sheet' => $cleanSheet
        ];
    }

    public function getPointsAttribute()
    {
        $result = self::calculatePoints($this->attributes);

        $total = $result['points'];
        $cleanSheet = $result['clean_sheet'];

        // Only save if values are different to avoid unnecessary writes/recursion
        if ($this->total_point !== $total || $this->clean_sheet !== $cleanSheet) {
            $this->total_point = $total;
            $this->clean_sheet = $cleanSheet;
            $this->saveQuietly(); 
        }

        return $total;
    }

    public function getPointsBreakdownAttribute()
    {
        $breakdown = [];

        if (!$this->did_play || $this->is_injured) {
             return $breakdown;
        }

        // Minutes Played (Base points)
        if (($this->minutes ?? 0) > 0) {
             $points = ($this->minutes >= 60) ? 2 : 1;
             $breakdown[] = [
                 'label' => 'Minutes played',
                 'value' => $this->minutes,
                 'points' => $points
             ];
        }

        // Goals
        if (($this->goals_total ?? 0) > 0) {
            $breakdown[] = [
                'label' => 'Goals',
                'value' => $this->goals_total,
                'points' => $this->goals_total * config('point.goal', 13)
            ];
        }

        // Assists
        if (($this->goals_assists ?? 0) > 0) {
            $breakdown[] = [
                'label' => 'Assists',
                'value' => $this->goals_assists,
                'points' => $this->goals_assists * config('point.assist', 7)
            ];
        }

        // Shots total
        if (($this->shots_total ?? 0) > 0) {
            $breakdown[] = [
                'label' => 'Shots total',
                'value' => $this->shots_total,
                'points' => $this->shots_total * config('point.shots_total', 2)
            ];
        }

        // Shots on target
        if (($this->shots_on_target ?? 0) > 0) {
            $breakdown[] = [
                'label' => 'Shots on target',
                'value' => $this->shots_on_target,
                'points' => $this->shots_on_target * config('point.shot_on_target', 1)
            ];
        }

        // Shots on goal
        if (isset($this->shots_on_goal) && $this->shots_on_goal !== $this->shots_on_target && ($this->shots_on_goal ?? 0) > 0) {
             $breakdown[] = [
                'label' => 'Shots on goal',
                'value' => $this->shots_on_goal,
                'points' => $this->shots_on_goal * config('point.shot_on_goal', 1)
            ];
        }

         // Yellow cards
         if (($this->yellow_cards ?? 0) > 0) {
            $breakdown[] = [
                'label' => 'Yellow cards',
                'value' => $this->yellow_cards,
                'points' => $this->yellow_cards * config('point.yellow_card', -1)
            ];
        }

        // Red cards
        if (($this->red_cards ?? 0) > 0) {
            $breakdown[] = [
                'label' => 'Red cards',
                'value' => $this->red_cards,
                'points' => $this->red_cards * config('point.red_card', -5)
            ];
        }

        // Fouls committed
        if (($this->fouls_committed ?? 0) > 0) {
            $breakdown[] = [
                'label' => 'Fouls committed',
                'value' => $this->fouls_committed,
                'points' => $this->fouls_committed * config('point.fouls_committed', -1)
            ];
        }

        // Tackles
        if (($this->tackles_total ?? 0) > 0) {
            $breakdown[] = [
                'label' => 'Tackles',
                'value' => $this->tackles_total,
                'points' => $this->tackles_total * config('point.tackle', 2)
            ];
        }

        // Goals Conceded breakdown
        // Logic inside calculatePoints adds this for G/D.
        if (in_array($this->position, ['G', 'D']) && ($this->goals_conceded ?? 0) > 0) {
             $breakdown[] = [
                'label' => 'Goals conceded',
                'value' => $this->goals_conceded,
                'points' => $this->goals_conceded * config('point.goal_concede', -2)
            ];
        }

        // Clean Sheet & Saves (GK/DEF)
        if (in_array($this->position, ['G', 'D']) && ($this->minutes ?? 0) >= 65) {
            $goalsConceeded = $this->goals_conceded ?? 0;

            if ($this->position === 'G') {
                 // Saves
                if (($this->goals_saves ?? 0) > 0) {
                    $breakdown[] = [
                        'label' => 'Saves',
                        'value' => $this->goals_saves,
                        'points' => $this->goals_saves * config('point.goals_saves', 3)
                    ];
                }

                if ($goalsConceeded === 0) {
                     $breakdown[] = [
                        'label' => 'Clean sheet',
                        'value' => 1,
                        'points' => config('point.clean_sheet_goalkeeper', 15)
                    ];
                }
            } else if ($this->position === 'D') {
                 if ($goalsConceeded === 0) {
                     $breakdown[] = [
                        'label' => 'Clean sheet',
                        'value' => 1,
                        'points' => config('point.clean_sheet_defender', 10)
                    ];
                }
            }
        }

        return $breakdown;
    }
}
