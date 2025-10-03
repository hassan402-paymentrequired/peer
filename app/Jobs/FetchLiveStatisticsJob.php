<?php

namespace App\Jobs;

use App\Models\Fixture;
use App\Models\PlayerMatch;
use App\Models\PlayerStatistic;
use App\Models\Tournament;
use App\Models\Peer;
use App\Jobs\CalculatScoresJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class FetchLiveStatisticsJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        Log::info('FetchLiveStatisticsJob started');

        try {
            // Get all active fixtures that have selected players
            $activeFixtures = $this->getActiveFixtures();

            if ($activeFixtures->isEmpty()) {
                Log::info('No active fixtures with selected players found');
                return;
            }

            Log::info('Found ' . $activeFixtures->count() . ' active fixtures to process');

            foreach ($activeFixtures as $fixture) {
                $this->processFixture($fixture);

                // Add delay to respect API rate limits
                sleep(1);
            }

            // Check if any competitions are now complete
            $this->checkCompletedCompetitions();
        } catch (\Exception $e) {
            Log::error('FetchLiveStatisticsJob failed: ' . $e->getMessage(), [
                'exception' => $e
            ]);
        }

        Log::info('FetchLiveStatisticsJob completed');
    }

    private function getActiveFixtures()
    {
        // Get fixtures that are ongoing or recently finished and have players selected in active competitions
        return Fixture::where(function ($query) {
            $query->where('status', 'Match Finished')
                ->orWhere('status', 'Second Half')
                ->orWhere('status', 'First Half')
                ->orWhere('status', 'Halftime');
        })
            ->whereHas('playerMatches.tournamentSquads')
            ->orWhereHas('playerMatches.peerSquads')
            ->distinct()
            ->get();
    }

    private function processFixture(Fixture $fixture): void
    {
        try {
            Log::info("Processing fixture {$fixture->external_id}");

            $statisticsData = $this->fetchFixtureStatistics($fixture->external_id);

            if (!$statisticsData) {
                Log::warning("No statistics data received for fixture {$fixture->external_id}");
                return;
            }

            $this->updatePlayerStatistics($fixture, $statisticsData);
        } catch (\Exception $e) {
            Log::error("Failed to process fixture {$fixture->external_id}: " . $e->getMessage());
        }
    }

    private function fetchFixtureStatistics(int $fixtureId): ?array
    {
        $apiKey = env('SPORT_API_KEY');
        $url = "https://v3.football.api-sports.io/fixtures/players";

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'x-rapidapi-key' => $apiKey
                ])
                ->get($url, [
                    'fixture' => $fixtureId
                ]);

            if (!$response->successful()) {
                Log::error("API request failed for fixture {$fixtureId}", [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return null;
            }

            $data = $response->json();
            return $data['response'] ?? null;
        } catch (\Exception $e) {
            Log::error("API request exception for fixture {$fixtureId}: " . $e->getMessage());
            return null;
        }
    }

    private function updatePlayerStatistics(Fixture $fixture, array $statisticsData): void
    {
        DB::beginTransaction();

        try {
            foreach ($statisticsData as $teamData) {
                $players = $teamData['players'] ?? [];

                foreach ($players as $playerData) {
                    $player = $playerData['player'] ?? [];
                    $statistics = $playerData['statistics'][0] ?? [];

                    if (empty($player['id']) || empty($statistics)) {
                        continue;
                    }

                    $this->updateOrCreatePlayerStatistic($fixture, $player, $statistics);
                }
            }

            DB::commit();
            Log::info("Updated statistics for fixture {$fixture->external_id}");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to update statistics for fixture {$fixture->external_id}: " . $e->getMessage());
            throw $e;
        }
    }

    private function updateOrCreatePlayerStatistic(Fixture $fixture, array $player, array $statistics): void
    {
        // Find the player in our database
        $localPlayer = \App\Models\Player::where('external_id', $player['id'])->first();

        if (!$localPlayer) {
            Log::warning("Player not found in database: {$player['id']}");
            return;
        }

        $games = $statistics['games'] ?? [];
        $goals = $statistics['goals'] ?? [];
        $passes = $statistics['passes'] ?? [];
        $shots = $statistics['shots'] ?? [];
        $cards = $statistics['cards'] ?? [];

        PlayerStatistic::updateOrCreate(
            [
                'player_id' => $localPlayer->id,
                'fixture_id' => $fixture->id,
            ],
            [
                'team_id' => $fixture->home_team_id, // This should be determined properly
                'match_date' => $fixture->date,
                'goals_total' => $goals['total'] ?? 0,
                'goals_assists' => $goals['assists'] ?? 0,
                'assists' => $goals['assists'] ?? 0,
                'shots_total' => $shots['total'] ?? 0,
                'shots_on_target' => $shots['on'] ?? 0,
                'yellow_cards' => $cards['yellow'] ?? 0,
                'minutes' => $games['minutes'] ?? 0,
                'rating' => $games['rating'] ?? null,
                'captain' => $games['captain'] ?? false,
                'substitute' => $games['substitute'] ?? false,
                'did_play' => ($games['minutes'] ?? 0) > 0,
                'is_injured' => false, // This would need to be determined from API
                'passes_total' => $passes['total'] ?? 0,
                'position' => $games['position'] ?? null,
                'offsides' => $statistics['offsides'] ?? 0,
                'tackles_total' => $statistics['tackles']['total'] ?? 0,
            ]
        );
    }

    private function checkCompletedCompetitions(): void
    {
        // Check tournaments
        $activeTournaments = Tournament::where('status', 'open')->get();

        foreach ($activeTournaments as $tournament) {
            if ($this->isTournamentComplete($tournament)) {
                Log::info("Tournament {$tournament->id} is complete, dispatching scoring job");
                CalculateCompetitionScoresJob::dispatch('tournament', $tournament->id);
            }
        }

        // Check peers
        $activePeers = Peer::where('status', 'open')->get();

        foreach ($activePeers as $peer) {
            if ($this->isPeerComplete($peer)) {
                Log::info("Peer {$peer->id} is complete, dispatching scoring job");
                CalculateCompetitionScoresJob::dispatch('peer', $peer->id);
            }
        }
    }

    private function isTournamentComplete(Tournament $tournament): bool
    {
        // Get all player matches for this tournament
        $playerMatchIds = DB::table('tournament_users')
            ->join('tournament_user_squards', 'tournament_users.id', '=', 'tournament_user_squards.tournament_user_id')
            ->where('tournament_users.tournament_id', $tournament->id)
            ->pluck('tournament_user_squards.main_player_match_id')
            ->merge(
                DB::table('tournament_users')
                    ->join('tournament_user_squards', 'tournament_users.id', '=', 'tournament_user_squards.tournament_user_id')
                    ->where('tournament_users.tournament_id', $tournament->id)
                    ->pluck('tournament_user_squards.sub_player_match_id')
            )
            ->unique()
            ->filter();

        if ($playerMatchIds->isEmpty()) {
            return false;
        }

        // Check if all fixtures for these player matches are finished
        $unfinishedCount = PlayerMatch::whereIn('id', $playerMatchIds)
            ->whereHas('fixture', function ($query) {
                $query->where('status', '!=', 'Match Finished');
            })
            ->count();

        return $unfinishedCount === 0;
    }

    private function isPeerComplete(Peer $peer): bool
    {
        // Get all player matches for this peer
        $playerMatchIds = DB::table('peer_users')
            ->join('peer_user_squards', 'peer_users.id', '=', 'peer_user_squards.peer_user_id')
            ->where('peer_users.peer_id', $peer->id)
            ->pluck('peer_user_squards.main_player_match_id')
            ->merge(
                DB::table('peer_users')
                    ->join('peer_user_squards', 'peer_users.id', '=', 'peer_user_squards.peer_user_id')
                    ->where('peer_users.peer_id', $peer->id)
                    ->pluck('peer_user_squards.sub_player_match_id')
            )
            ->unique()
            ->filter();

        if ($playerMatchIds->isEmpty()) {
            return false;
        }

        // Check if all fixtures for these player matches are finished
        $unfinishedCount = PlayerMatch::whereIn('id', $playerMatchIds)
            ->whereHas('fixture', function ($query) {
                $query->where('status', '!=', 'Match Finished');
            })
            ->count();

        return $unfinishedCount === 0;
    }
}
