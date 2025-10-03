<?php

namespace App\Jobs;

use App\Models\Fixture;
use App\Models\PlayerMatch;
use App\Models\PlayerStatistic;
use App\Models\Tournament;
use App\Models\Peer;
use App\Models\FixtureLineup;
use App\Jobs\CalculateCompetitionScoresJob;
use App\Jobs\FetchFixtureLineupsJob;
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
        // Only fetch statistics for fixtures that are:
        // 1. Currently ongoing (to get live updates)
        // 2. Just finished (to get final stats)
        return Fixture::where(function ($query) {
            $query->whereIn('status', [
                'First Half',
                'Second Half',
                'Halftime',
                'Extra Time',
                'Penalty In Progress',
                'Match Finished'  // Include just finished matches
            ]);
        })
            ->where('date', '>=', now()->subHours(6)) // Only recent matches
            ->where('date', '<=', now()->addHours(3))  // Don't fetch future matches
            ->whereHas('playerMatches.tournamentSquads')
            ->orWhereHas('playerMatches.peerSquads')
            ->distinct()
            ->get();
    }

    private function processFixture(Fixture $fixture): void
    {
        try {
            Log::info("Processing fixture {$fixture->external_id}");

            // Lineup data should already be available from weekly fetch

            $statisticsData = $this->fetchFixtureStatistics($fixture->external_id);

            if (!$statisticsData) {
                Log::warning("No statistics data received for fixture {$fixture->external_id}");
                return;
            }

            $this->updatePlayerStatistics($fixture, $statisticsData);

            // Mark player matches as completed if fixture is finished
            if ($fixture->status === 'Match Finished') {
                $this->markPlayerMatchesCompleted($fixture);
            }
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
        // Find the player in our database using external_id
        $localPlayer = \App\Models\Player::where('external_id', $player['id'])->first();

        if (!$localPlayer) {
            Log::warning("Player not found in database: {$player['id']}");
            return;
        }

        // Extract statistics from API response structure
        $games = $statistics['games'] ?? [];
        $goals = $statistics['goals'] ?? [];
        $passes = $statistics['passes'] ?? [];
        $shots = $statistics['shots'] ?? [];
        $cards = $statistics['cards'] ?? [];
        $tackles = $statistics['tackles'] ?? [];

        // Determine which team this player belongs to
        $teamId = $this->determinePlayerTeam($fixture, $player['id']);

        PlayerStatistic::updateOrCreate(
            [
                'player_id' => $localPlayer->id,
                'fixture_id' => $fixture->id,
            ],
            [
                'team_id' => $teamId,
                'match_date' => $fixture->date,

                // Goals and assists (mapped correctly from API)
                'goals_total' => $goals['total'] ?? 0,
                'goals_assists' => $goals['assists'] ?? 0,
                'assists' => $goals['assists'] ?? 0,  // Same as goals_assists

                // Shots
                'shots_total' => $shots['total'] ?? 0,
                'shots_on_target' => $shots['on'] ?? 0,

                // Cards
                'yellow_cards' => $cards['yellow'] ?? 0,

                // Game info
                'minutes' => $games['minutes'] ?? 0,
                'rating' => $games['rating'] ?? null,
                'captain' => $games['captain'] ?? false,
                'substitute' => $games['substitute'] ?? false,
                'position' => $games['position'] ?? null,
                'number' => $games['number'] ?? null,

                // Playing status
                'did_play' => ($games['minutes'] ?? 0) > 0,
                'is_injured' => false, // API doesn't provide this directly

                // Additional stats
                'passes_total' => $passes['total'] ?? 0,
                'offsides' => $statistics['offsides'] ?? 0,
                'tackles_total' => $tackles['total'] ?? 0,

                // Goalkeeper specific
                'goals_conceded' => $goals['conceded'] ?? 0,
                'goals_saves' => $goals['saves'] ?? 0,
            ]
        );
    }

    /**
     * Determine which team a player belongs to in this fixture
     */
    private function determinePlayerTeam(Fixture $fixture, int $playerExternalId): int
    {
        // Check if we have lineup data to determine team
        $lineup = FixtureLineup::where('fixture_id', $fixture->id)
            ->get()
            ->first(function ($lineup) use ($playerExternalId) {
                return $lineup->hasPlayer($playerExternalId);
            });

        if ($lineup) {
            return $lineup->team_id;
        }

        // Fallback to home team if we can't determine
        return $fixture->home_team_id;
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



    private function markPlayerMatchesCompleted(Fixture $fixture): void
    {
        try {
            $updatedCount = PlayerMatch::where('fixture_id', $fixture->id)
                ->where('is_completed', false)
                ->update(['is_completed' => true]);

            if ($updatedCount > 0) {
                Log::info("Marked {$updatedCount} player matches as completed for fixture {$fixture->external_id}");
            }
        } catch (\Exception $e) {
            Log::error("Failed to mark player matches as completed for fixture {$fixture->external_id}: " . $e->getMessage());
        }
    }
}
