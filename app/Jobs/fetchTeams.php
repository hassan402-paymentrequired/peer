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
        Log::info('Fetching teams for league: ' . $this->league . ' and year: ' . $this->year);
        $leagueId = $this->league;
        $season = $this->year;
        $apiUrl = 'https://v3.football.api-sports.io/teams';
        $apiKey = env('SPORT_API_KEY');
        $page = 1;
        $totalPages = 1;

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


            $teams = $body['response'] ?? [];
            $paging = $body['paging'] ?? ['current' => $page, 'total' => $page];
            $totalPages = $paging['total'] ?? 1;

            collect($teams)->chunk(1000)->each(function ($chunk) use (&$insertBatch) {
                foreach ($chunk as $item) {
                    $team = $item['team'];

                    \App\Models\Team::query()->updateOrCreate(
                        ['external_id' => $team['id'] ],
                        [
                            'name'        => $team['name'],
                            'code'        => $team['code'] ?? '',
                            'country'     => $team['country'] ?? '',
                            'logo'        => $team['logo'] ?? '',
                        ]
                    );
                }
            });

            $page++;
        } while ($page <= $totalPages);

        Log::info('All teams fetched and inserted/updated successfully.');
        Log::info('Starting to fetch team players.');

        $this->fetchTeamPlayer($leagueId, $season);
    }

    public function fetchTeamPlayer($leagueId, $season)
    {
        FetchPlayers::dispatch($leagueId, $season);
    }
}
