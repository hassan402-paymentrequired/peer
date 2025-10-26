<?php

namespace App\Jobs;

use App\Models\Peer;
use App\Models\PeerUser;
use App\Models\PlayerStatistic;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class UpdatePeerAndTournamentTotalPoint implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Updating total point for peer and tournament');

        $peers = Peer::query()
            ->where('status', 'open')
            ->chunk(300, function ($peers) {
                $peers->each(function ($peer) {
                    PeerUser::with(['squads.mainPlayer', 'squads.subPlayer', 'user'])
                        ->where('peer_id', $peer->id)
                        ->chunk(300, function ($participants) {
                            Log::info("Calculating scores for the chunk of {$participants->count()} peer participants");
                            $participants->each(function ($participant) {

                                $totalPoints = $this->calculateParticipantScore($participant->squads);

                                $participant->update(['total_points' => $totalPoints]);

                                Log::info("Updated participant {$participant->user_id} with {$totalPoints} points");
                            });
                        });
                });
            });
    }

    private function calculateParticipantScore($squads): int
    {
        $totalPoints = 0;

        foreach ($squads as $squad) {
            // Calculate main player points
            $mainPlayerPoints = $this->getPlayerPoints($squad->main_player_id, $squad->main_player_match_id);

            // Check if main player played (has points > 0 or played the match)
            $mainPlayerPlayed = $this->didPlayerPlay($squad->main_player_id, $squad->main_player_match_id);

            if ($mainPlayerPlayed) {
                // Use main player points
                $squadPoints = $mainPlayerPoints;
                $usedPlayer = 'main';
            } else {
                // Use sub player points since main didn't play
                $subPlayerPoints = $this->getPlayerPoints($squad->sub_player_id, $squad->sub_player_match_id);
                $squadPoints = $subPlayerPoints;
                $usedPlayer = 'sub';
            }

            $totalPoints += $squadPoints;

            Log::debug("Squad points calculated", [
                'main_player_id' => $squad->main_player_id,
                'main_player_points' => $mainPlayerPoints,
                'sub_player_id' => $squad->sub_player_id,
                'sub_player_points' => $usedPlayer === 'sub' ? $squadPoints : 0,
                'used_player' => $usedPlayer,
                'squad_total' => $squadPoints
            ]);
        }

        return $totalPoints;
    }

    private function getPlayerPoints(int $playerId, ?int $playerMatchId): int
    {
        if (!$playerMatchId) {
            return 0;
        }

        // Get the fixture_id from player_match
        $playerMatch = \App\Models\PlayerMatch::find($playerMatchId);
        if (!$playerMatch || !$playerMatch->fixture_id) {
            return 0;
        }

        // Get player statistics for this fixture
        $statistic = PlayerStatistic::where('player_id', $playerId)
            ->where('fixture_id', $playerMatch->fixture_id)
            ->first();

        if (!$statistic) {
            Log::warning("No statistics found for player {$playerId} in fixture {$playerMatch->fixture_id}");
            return 0;
        }

        // Use the model's getPointsAttribute method
        return $statistic->points ?? 0;
    }

     private function didPlayerPlay(int $playerId, ?int $playerMatchId): bool
    {
        if (!$playerMatchId) {
            return false;
        }

        // Get the fixture_id from player_match
        $playerMatch = \App\Models\PlayerMatch::find($playerMatchId);
        if (!$playerMatch || !$playerMatch->fixture_id) {
            return false;
        }

        // Get player statistics for this fixture
        $statistic = PlayerStatistic::where('player_id', $playerId)
            ->where('fixture_id', $playerMatch->fixture_id)
            ->first();

        if (!$statistic) {
            return false;
        }

        // Player played if they have did_play = true and are not injured
        return $statistic->did_play && !$statistic->is_injured;
    }
}
