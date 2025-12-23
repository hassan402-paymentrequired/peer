<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TournamentUser extends Model
{
    protected $fillable = [
        'tournament_id',
        'user_id',
        'total_points',
        'is_winner'
    ];


    public function daily_contest()
    {
        return $this->belongsTo(Tournament::class);
    }

    public function tournament()
    {
        return $this->belongsTo(Tournament::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }


    public function squads()
    {
        return $this->hasMany(TournamentUserSquard::class);
    }

    public function calculateLiveScore(): int
    {
        $totalPoints = 0;

        foreach ($this->squads as $squad) {
            // Get statistics for the main player
            $mainPlayerMatch = PlayerMatch::find($squad->main_player_match_id);
            $main_fixture_id = $mainPlayerMatch ? $mainPlayerMatch->fixture_id : null;

            $mainStats = null;
            if ($main_fixture_id) {
                $mainStats = PlayerStatistic::where('player_id', $squad->main_player_id)
                    ->where('fixture_id', $main_fixture_id)
                    ->first();
            }

            // Check if main player played
            if ($mainStats && $mainStats->did_play && !$mainStats->is_injured) {
                $totalPoints += $mainStats->total_point ?? 0;
            } else {
                // If main player didn't play, try the sub
                $subPlayerMatch = PlayerMatch::find($squad->sub_player_match_id);
                $sub_fixture_id = $subPlayerMatch ? $subPlayerMatch->fixture_id : null;

                $subStats = null;
                if ($sub_fixture_id) {
                    $subStats = PlayerStatistic::where('player_id', $squad->sub_player_id)
                        ->where('fixture_id', $sub_fixture_id)
                        ->first();
                }

                if ($subStats) {
                    $totalPoints += $subStats->total_point ?? 0;
                }
            }
        }

        return $totalPoints;
    }
}
