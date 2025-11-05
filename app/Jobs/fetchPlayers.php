<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class FetchPlayers implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public string $league,  public string $year)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $leagueId = $this->league;
        $season = $this->year;
        $apiUrl = 'https://v3.football.api-sports.io/players';
        $apiKey = env('SPORT_API_KEY');
        $page = 1;

        while (true) {
            Log::info("Fetching players for league $leagueId, season $season, page $page...");
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'x-rapidapi-key' => $apiKey
            ])->get($apiUrl, [
                'league' => $leagueId,
                'season' => $season,
                'page' => $page
            ]);


            $body = $response->json();
            $players = $body['response'] ?? [];
            $paging = $body['paging'] ?? ['current' => $page, 'total' => $page];
            $currentPage = $paging['current'] ?? $page;
            $totalPages = $paging['total'] ?? $page;

            Log::info("Total players fetched for page {$page}: " . count($players));

            foreach ($players as $item) {
                $player = $item['player'];
                $stats = $item['statistics'][0] ?? [];
                $team = $stats['team'] ?? [];
                $games = $stats['games'] ?? [];
                $position = $games['position'] ?? '';

                \App\Models\Player::query()->updateOrCreate(
                    ['external_id'  => $player['id'],],
                    [
                        'name'         => $player['name'],
                        'team_id'      => $team['id'] ?? '',
                        'position'     => $position,
                        'image'        => $player['photo'] ?? '',
                        'nationality'  => $player['nationality'] ?? '',
                        'player_rating' => random_int(1, 5),
                    ]
                );
            }

            if ($currentPage >= $totalPages) {
                break;
            }
            $page++;
        }

        Log::info('All players fetched and inserted/updated successfully.');
    }
}
