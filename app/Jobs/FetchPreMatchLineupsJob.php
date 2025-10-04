<?php

namespace App\Jobs;

use App\Models\Fixture;
use App\Models\FixtureLineup;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FetchPreMatchLineupsJob implements ShouldQueue
{
    use Queueable;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 300; // 5 minutes

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    public function handle(): void
    {
        Log::info('FetchPreMatchLineupsJob started - fetching lineups for upcoming matches');

        try {
            // Get fixtures that are starting in the next 2 hours and don't have lineups yet
            $upcomingFixtures = $this->getUpcomingFixtures();

            if ($upcomingFixtures->isEmpty()) {
                Log::info('No upcoming fixtures needing lineups found');
                return;
            }

            Log::info('Found ' . $upcomingFixtures->count() . ' upcoming fixtures needing lineups');

            $successCount = 0;
            $noLineupsCount = 0;
            $errorCount = 0;

            foreach ($upcomingFixtures as $fixture) {
                try {
                    $result = $this->fetchLineupForFixture($fixture);

                    if ($result === 'success') {
                        $successCount++;
                    } elseif ($result === 'no_lineups') {
                        $noLineupsCount++;
                    }

                    // Add delay to respect API rate limits
                    sleep(2);
                } catch (\Exception $e) {
                    $errorCount++;
                    Log::error("Failed to fetch lineup for fixture {$fixture->external_id}: " . $e->getMessage());
                }
            }

            Log::info("Pre-match lineup fetching completed", [
                'success' => $successCount,
                'no_lineups_yet' => $noLineupsCount,
                'errors' => $errorCount
            ]);
        } catch (\Exception $e) {
            Log::error('FetchPreMatchLineupsJob failed: ' . $e->getMessage(), [
                'exception' => $e
            ]);
        }
    }

    private function getUpcomingFixtures()
    {
        // Get fixtures that:
        // 1. Are starting in the next 2 hours
        // 2. Don't have lineups yet
        // 3. Have players selected in tournaments/peers
        return Fixture::where('date', '>=', now())
            ->where('date', '<=', now()->addHours(2))
            ->whereIn('status', ['Not Started', 'TBD'])
            ->whereDoesntHave('lineups')
            ->where(function ($query) {
                $query->whereHas('playerMatches.tournamentSquads')
                    ->orWhereHas('playerMatches.peerSquads');
            })
            ->get();
    }

    private function fetchLineupForFixture(Fixture $fixture): string
    {
        $apiKey = env('SPORT_API_KEY');
        $url = "https://v3.football.api-sports.io/fixtures/lineups";

        Log::info("Fetching pre-match lineup for fixture {$fixture->external_id}");

        $response = Http::timeout(30)
            ->withHeaders([
                'x-rapidapi-key' => $apiKey
            ])
            ->get($url, [
                'fixture' => $fixture->external_id
            ]);

        if (!$response->successful()) {
            Log::warning("Lineup API request failed for fixture {$fixture->external_id}", [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            return 'error';
        }

        $data = $response->json();
        $lineupData = $data['response'] ?? [];

        if (empty($lineupData)) {
            Log::info("No lineup data available yet for fixture {$fixture->external_id} (lineups typically available 20-40 minutes before kickoff)");
            return 'no_lineups';
        }

        $this->storeLineupData($fixture, $lineupData);
        return 'success';
    }

    private function storeLineupData(Fixture $fixture, array $lineupData): void
    {
        DB::beginTransaction();

        try {
            // Clear existing lineups for this fixture (in case of re-fetch)
            FixtureLineup::where('fixture_id', $fixture->id)->delete();

            foreach ($lineupData as $teamLineup) {
                $team = $teamLineup['team'] ?? [];
                $startingXI = $teamLineup['startXI'] ?? [];
                $substitutes = $teamLineup['substitutes'] ?? [];
                $coach = $teamLineup['coach'] ?? null;

                FixtureLineup::create([
                    'fixture_id' => $fixture->id,
                    'team_id' => $team['id'] ?? 0,
                    'team_name' => $team['name'] ?? 'Unknown',
                    'formation' => $teamLineup['formation'] ?? null,
                    'starting_xi' => $startingXI,
                    'substitutes' => $substitutes,
                    'coach' => $coach,
                    'raw_data' => $teamLineup
                ]);
            }

            DB::commit();
            Log::info("Stored pre-match lineup data for fixture {$fixture->external_id}");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to store lineup data for fixture {$fixture->external_id}: " . $e->getMessage());
            throw $e;
        }
    }
}
