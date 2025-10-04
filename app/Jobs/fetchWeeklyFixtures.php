<?php

namespace App\Jobs;

use App\Models\Fixture;
use App\Models\FixtureLineup;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class fetchWeeklyFixtures implements ShouldQueue
{
    use Queueable;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 1800; // 30 minutes for weekly fixture fetch

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 2;

    /**
     * Create a new job instance.
     */
    public function __construct(public string $league, public string $season, public string $to, public string $from)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $league = $this->league;
        $season = $this->season;
        $from = $this->from;
        $to = $this->to;
        $apiUrl = 'https://v3.football.api-sports.io/fixtures';
        $apiKey = env('SPORT_API_KEY');
        $page = 1;
        $totalPages = 1;

        do {
            Log::info("Fetching fixtures for league $league, season $season, from $from to $to, page $page...");
            $response = Http::withHeaders([
                'x-rapidapi-key' => $apiKey
            ])->get($apiUrl, [
                'league' => $league,
                'season' => $season,
                'from' => $from,
                'to' => $to,
                // 'page' => $page
            ]);

            // Log::info("Status code: " ,[ $response]);

            // if (!$response->ok()) {
            //     Log::error('Failed to fetch fixtures: ' . $response->body());
            // }

            $body = $response->json();
            $fixtures = $body['response'] ?? [];
            $paging = $body['paging'] ?? ['current' => $page, 'total' => $page];
            $currentPage = $paging['current'] ?? $page;
            $totalPages = $paging['total'] ?? $page;

            Log::info("Total fixtures fetched: " . count($fixtures));
            // Log::info(json_encode($body));

            foreach ($fixtures as $item) {
                $fixture = $item['fixture'];
                $leagueData = $item['league'];
                $teams = $item['teams'];
                $venue = $fixture['venue'] ?? [];
                $goals = $item['goals'] ?? [];
                $score = $item['score'] ?? [];
                $halftime = $score['halftime'] ?? [];
                $fulltime = $score['fulltime'] ?? [];
                $fx = Fixture::updateOrCreate(
                    [
                        'external_id' => $fixture['id'],
                    ],
                    [
                        'league_id' => $leagueData['id'],
                        'season' => $leagueData['season'],
                        'date' => $fixture['date'],
                        'timestamp' => $fixture['timestamp'],
                        'venue_id' => $venue['id'] ?? null,
                        'venue_name' => $venue['name'] ?? null,
                        'venue_city' => $venue['city'] ?? null,
                        'home_team_id' => $teams['home']['id'],
                        'home_team_name' => $teams['home']['name'],
                        'home_team_logo' => $teams['home']['logo'] ?? null,
                        'away_team_id' => $teams['away']['id'],
                        'away_team_name' => $teams['away']['name'],
                        'away_team_logo' => $teams['away']['logo'] ?? null,
                        'status' => $fixture['status']['long'] ?? null,
                        'goals_home' => $goals['home'] ?? null,
                        'goals_away' => $goals['away'] ?? null,
                        'score_halftime_home' => $halftime['home'] ?? null,
                        'score_halftime_away' => $halftime['away'] ?? null,
                        'score_fulltime_home' => $fulltime['home'] ?? null,
                        'score_fulltime_away' => $fulltime['away'] ?? null,
                        'raw_json' => json_encode($item),
                    ]
                );

                // Lineups will be fetched automatically 20-60 minutes before kickoff
            }

            $page++;
        } while ($page <= $totalPages);

        Log::info('All fixtures fetched and upserted successfully.');

        // Note: Lineups are only available 20-40 minutes before kickoff
        // We'll fetch them separately closer to match time
        Log::info('Fixtures fetched. Lineups will be fetched closer to match time (20-40 minutes before kickoff).');
    }

    /**
     * Fetch lineups for all fixtures in the date range
     */
    private function fetchLineupsForWeeklyFixtures(string $league, string $season, string $from, string $to): void
    {
        Log::info("Starting to fetch lineups for fixtures from $from to $to...");

        // Get all fixtures for this date range that don't have lineups yet
        $fixtures = Fixture::where('league_id', $league)
            ->where('season', $season)
            ->whereBetween('date', [$from, $to])
            ->whereDoesntHave('lineups')
            ->get();

        Log::info("Found " . $fixtures->count() . " fixtures needing lineups");

        $successCount = 0;
        $errorCount = 0;

        foreach ($fixtures as $fixture) {
            try {
                $this->fetchLineupForFixture($fixture);
                $successCount++;

                // Add delay to respect API rate limits
                sleep(2); // 2 seconds between lineup requests

            } catch (\Exception $e) {
                $errorCount++;
                Log::error("Failed to fetch lineup for fixture {$fixture->external_id}: " . $e->getMessage());
            }
        }

        Log::info("Lineup fetching completed. Success: $successCount, Errors: $errorCount");
    }

    /**
     * Fetch lineup for a specific fixture
     */
    private function fetchLineupForFixture(Fixture $fixture): void
    {
        $apiKey = env('SPORT_API_KEY');
        $url = "https://v3.football.api-sports.io/fixtures/lineups";

        Log::info("Fetching lineup for fixture {$fixture->external_id}");

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
            Log::info("No lineup data available for fixture {$fixture->external_id} (lineups may not be announced yet)");
            return;
        }

        $this->storeLineupData($fixture, $lineupData);
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
            Log::info("Stored lineup data for fixture {$fixture->external_id}");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to store lineup data for fixture {$fixture->external_id}: " . $e->getMessage());
            throw $e;
        }
    }
}
