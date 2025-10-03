<?php

namespace App\Jobs;

use App\Models\Fixture;
use App\Models\FixtureLineup;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class FetchFixtureLineupsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ?int $fixtureId = null // If null, fetch for all relevant fixtures
    ) {}

    public function handle(): void
    {
        Log::info('FetchFixtureLineupsJob started', ['fixture_id' => $this->fixtureId]);

        try {
            if ($this->fixtureId) {
                // Fetch lineup for specific fixture
                $fixture = Fixture::findOrFail($this->fixtureId);
                $this->fetchLineupForFixture($fixture);
            } else {
                // Fetch lineups for all relevant fixtures
                $fixtures = $this->getFixturesNeedingLineups();

                Log::info('Found ' . $fixtures->count() . ' fixtures needing lineups');

                foreach ($fixtures as $fixture) {
                    $this->fetchLineupForFixture($fixture);

                    // Add delay to respect API rate limits
                    sleep(1);
                }
            }
        } catch (\Exception $e) {
            Log::error('FetchFixtureLineupsJob failed: ' . $e->getMessage(), [
                'fixture_id' => $this->fixtureId,
                'exception' => $e
            ]);
        }

        Log::info('FetchFixtureLineupsJob completed');
    }

    private function getFixturesNeedingLineups()
    {
        // Get fixtures that:
        // 1. Are starting soon, ongoing, or recently finished
        // 2. Have players selected in tournaments/peers
        // 3. Don't already have lineups fetched
        return Fixture::where(function ($query) {
            $query->whereIn('status', [
                'Not Started',
                'First Half',
                'Second Half',
                'Halftime',
                'Extra Time',
                'Penalty In Progress',
                'Match Finished'
            ])
                ->where('date', '>=', now()->subHours(3)) // Only recent/upcoming matches
                ->where('date', '<=', now()->addHours(6));
        })
            ->whereHas('playerMatches.tournamentSquads')
            ->orWhereHas('playerMatches.peerSquads')
            ->whereDoesntHave('lineups')
            ->distinct()
            ->get();
    }

    private function fetchLineupForFixture(Fixture $fixture): void
    {
        try {
            Log::info("Fetching lineup for fixture {$fixture->external_id}");

            $lineupData = $this->fetchLineupFromAPI($fixture->external_id);

            if (!$lineupData) {
                Log::warning("No lineup data received for fixture {$fixture->external_id}");
                return;
            }

            $this->storeLineupData($fixture, $lineupData);
        } catch (\Exception $e) {
            Log::error("Failed to fetch lineup for fixture {$fixture->external_id}: " . $e->getMessage());
        }
    }

    private function fetchLineupFromAPI(int $fixtureId): ?array
    {
        $apiKey = env('SPORT_API_KEY');
        $url = "https://v3.football.api-sports.io/fixtures/lineups";

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'x-rapidapi-key' => $apiKey
                ])
                ->get($url, [
                    'fixture' => $fixtureId
                ]);

            if (!$response->successful()) {
                Log::error("Lineup API request failed for fixture {$fixtureId}", [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return null;
            }

            $data = $response->json();
            return $data['response'] ?? null;
        } catch (\Exception $e) {
            Log::error("Lineup API request exception for fixture {$fixtureId}: " . $e->getMessage());
            return null;
        }
    }

    private function storeLineupData(Fixture $fixture, array $lineupData): void
    {
        DB::beginTransaction();

        try {
            // Clear existing lineups for this fixture
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
            Log::info("Stored lineup data for fixture {$fixture->external_id}");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to store lineup data for fixture {$fixture->external_id}: " . $e->getMessage());
            throw $e;
        }
    }
}
