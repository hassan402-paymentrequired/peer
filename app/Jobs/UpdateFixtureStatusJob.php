<?php

namespace App\Jobs;

use App\Enum\FixtureStatusEnum;
use App\Models\Fixture;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class UpdateFixtureStatusJob implements ShouldQueue
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
        Log::info('UpdateFixtureStatusJob started');

        try {
            // Get fixtures that might need status updates
            $activeFixtures = $this->getActiveFixtures();

            if ($activeFixtures->isEmpty()) {
                Log::info('No active fixtures found for status update');
                return;
            }

            Log::info('Found ' . $activeFixtures->count() . ' fixtures to check for status updates');

            foreach ($activeFixtures as $fixture) {
                $this->updateFixtureStatus($fixture);

                // Add delay to respect API rate limits
                sleep(1);
            }
        } catch (\Exception $e) {
            Log::error('UpdateFixtureStatusJob failed: ' . $e->getMessage(), [
                'exception' => $e
            ]);
        }

        Log::info('UpdateFixtureStatusJob completed');
    }

    /**
     * Get fixtures that might need status updates
     */
    private function getActiveFixtures()
    {
        return Fixture::active()->distinct()->get();
    }

    /**
     * Update status for a specific fixture
     */
    private function updateFixtureStatus(Fixture $fixture): void
    {
        $apiKey = env('SPORT_API_KEY');
        $url = "https://v3.football.api-sports.io/fixtures";

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'x-rapidapi-key' => $apiKey
                ])
                ->get($url, [
                    'id' => $fixture->external_id
                ]);

            if (!$response->successful()) {
                Log::warning("Status API request failed for fixture {$fixture->external_id}", [
                    'status' => $response->status()
                ]);
                return;
            }

            $data = $response->json();
            $fixtures = $data['response'] ?? [];

            if (empty($fixtures)) {
                Log::warning("No fixture data received for {$fixture->external_id}");
                return;
            }

            $fixtureData = $fixtures[0];
            $newStatus = $fixtureData['fixture']['status']['long'] ?? null;
            $goals = $fixtureData['goals'] ?? [];
            $score = $fixtureData['score'] ?? [];

            if ($newStatus && $newStatus !== $fixture->status) {
                $oldStatus = $fixture->status;

                $fixture->update([
                    'status' => $newStatus,
                    'goals_home' => $goals['home'] ?? $fixture->goals_home,
                    'goals_away' => $goals['away'] ?? $fixture->goals_away,
                    'score_halftime_home' => $score['halftime']['home'] ?? $fixture->score_halftime_home,
                    'score_halftime_away' => $score['halftime']['away'] ?? $fixture->score_halftime_away,
                    'score_fulltime_home' => $score['fulltime']['home'] ?? $fixture->score_fulltime_home,
                    'score_fulltime_away' => $score['fulltime']['away'] ?? $fixture->score_fulltime_away,
                ]);

                Log::info("Updated fixture {$fixture->external_id} status: {$oldStatus} → {$newStatus}");

                if ($newStatus === FixtureStatusEnum::MATCH_FINISHED->value && $oldStatus !== FixtureStatusEnum::MATCH_FINISHED->value) {
                    Log::info("Match finished, will fetch final statistics for fixture {$fixture->external_id}");
                    // The FetchLiveStatisticsJob will handle this in its next run
                    FetchLiveStatisticsJob::dispatch($fixture);
                }
            }
        } catch (\Exception $e) {
            Log::error("Failed to update status for fixture {$fixture->external_id}: " . $e->getMessage());
        }
    }
}
