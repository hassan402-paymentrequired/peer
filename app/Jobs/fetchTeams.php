<?php

namespace App\Jobs;

use App\Models\League;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class FetchTeams implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public string $league, public string $year)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $leagueSeason = League::query()->first();
        Log::info('Fetching teams for league: ' . json_encode($leagueSeason->toArray()));
        $leagueId = $this->league;
        $season = $this->year;
        $apiUrl = 'https://v3.football.api-sports.io/teams';
        $apiKey = env('SPORT_API_KEY');
        $page = 1;
        $totalPages = 1;
        $insertBatch = [];

        do {
            Log::info("Fetching teams for league $leagueId, season $season, page $page...");
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'x-rapidapi-key' => $apiKey
            ])->get($apiUrl, [
                'league' => $leagueId,
                'season' => $season,
            ]);

            if (!$response->ok()) {
                Log::error('Failed to fetch teams: ' . $response->body());
            }

            $body = $response->json();

            // Log::info(json_encode($body));

            $teams = $body['response'] ?? [];
            $paging = $body['paging'] ?? ['current' => $page, 'total' => $page];
            $totalPages = $paging['total'] ?? 1;

            foreach ($teams as $item) {
                $team = $item['team'];
                $insertBatch[] = [
                    'external_id' => $team['id'],
                    'name'        => $team['name'],
                    'code'        => $team['code'] ?? '',
                    'country'     => $team['country'] ?? '',
                    'logo'        => $team['logo'] ?? '',
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ];
            }

            if (count($insertBatch) >= 500) {
                \App\Models\Team::upsert($insertBatch, ['external_id'], ['name', 'code', 'country', 'logo', 'status', 'updated_at']);
                $insertBatch = [];
            }

            $page++;
        } while ($page <= $totalPages);

        if (!empty($insertBatch)) {
            \App\Models\Team::upsert($insertBatch, ['external_id'], ['name', 'code', 'country', 'logo', 'status', 'updated_at']);
        }

        Log::info('All teams fetched and inserted/updated successfully.');
    }
}
