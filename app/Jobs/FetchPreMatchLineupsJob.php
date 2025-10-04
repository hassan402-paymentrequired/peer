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
        Log::info('FetchPreMatchLineupsJob started');

        try {
            // Get fixtures that are starting in the next 20-60 minutes and don't have lineups yet
            $upcomingFixtures = $this->getUpcomingFixturesNeedingLineups();

            if ($upcomingFixtures->isEmpty()) {
                Log::info('No upcoming fixtures needing lineups found');
                return;
            }

            Log::info('Found ' . $upcomingFixtures->count() . ' upcoming fixtures needing lineups');

            foreach ($upcomingFixtures as $fixture) {
                $this->fetchLineupForFixture($fixture);

                // Add delay to respect API rate limits
                sleep(2);
            }
        } catch (\Exception $e) {
            Log::error('FetchPreMatchLineupsJob failed: ' . $e->getMessage(), [
                'exception' => $e
            ]);
        }

        Log::info('FetchPreMatchLineupsJob completed');
    }

    /**
     * Get fixtures that are starting soon and need lineups
     */
    private function getUpcomingFixturesNeedingLineups()
    {
        $now = Carbon::now();

        return Fixture::where('date', '>=', $now->copy()->addMinutes(20)) // At least 20 minutes away
            ->where('date', '<=', $now->copy()->addMinutes(60))           // But within 60 minutes
            ->whereIn('status', ['Not Started', 'TBD'])                   // Only upcoming matches
            ->whereDoesntHave('lineups')                                  // Don't have lineups yet
            ->whereHas('playerMatches')                                   // Have players selected
            ->get();
    }

    /**
     * Fetch lineup for a specific fixture
     */
    private function fetchLineupForFixture(Fixture $fixture): void
    {
        $apiKey = env('SPORT_API_KEY');
        $url = "https://v3.football.api-sports.io/fixtures/lineups";

        Log::info("Fetching pre-match lineup for fixture {$fixture->external_id}");

        try {
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
                return;
            }

            $data = $response->json();
            $lineupData = $data['response'] ?? [];

            if (empty($lineupData)) {
                Log::info("No lineup data available yet for fixture {$fixture->external_id} (may be too early)");
                return;
            }

            $this->storeLineupData($fixture, $lineupData);
        } catch (\Exception $e) {
            Log::error("Failed to fetch lineup for fixture {$fixture->external_id}: " . $e->getMessage());
        }
    }

    /**
     * Store lineup data in the database
     */
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
