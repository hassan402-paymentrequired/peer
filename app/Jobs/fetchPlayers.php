<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class fetchPlayers implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public string $league)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $leagueId = $this->league;
        $season = 2023;
        $apiUrl = 'https://v3.football.api-sports.io/players';
        $apiKey = env('SPORT_API_KEY');
        $page = 1;
        $insertBatch = [];

        while (true) {
            Log::info("Fetching players for league $leagueId, season $season, page $page...");
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'x-rapidapi-key' => $apiKey
            ])->get($apiUrl, [
                'league' => $leagueId,
                'season' => $season,
                'page' => $page
            ]);

            if (!$response->ok()) {
                Log::error('Failed to fetch players: ' . $response->body());
            }

            $body = $response->json();
            Log::info('Fetch Players Response', $body);
            $players = $body['response'] ?? [];
            $paging = $body['paging'] ?? ['current' => $page, 'total' => $page];
            $currentPage = $paging['current'] ?? $page;
            $totalPages = $paging['total'] ?? $page;

            Log::info("Total players fetched: " . count($players));

            foreach ($players as $item) {
                $player = $item['player'];
                $stats = $item['statistics'][0] ?? [];
                $team = $stats['team'] ?? [];
                $games = $stats['games'] ?? [];
                $position = $games['position'] ?? '';
                $insertBatch[] = [
                    'external_id'  => $player['id'],
                    'name'         => $player['name'],
                    'team_id'      => $team['id'] ?? '',
                    'position'     => $position,
                    'image'        => $player['photo'] ?? '',
                    'nationality'  => $player['nationality'] ?? '',
                    'player_rating' => 1,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ];
            }

            if (count($insertBatch) >= 500) {
                \App\Models\Player::upsert($insertBatch, ['external_id'], ['name', 'team_id', 'position', 'image', 'nationality', 'player_rating', 'updated_at']);
                $insertBatch = [];
            }

            if ($currentPage >= $totalPages) {
                break;
            }
            $page++;
        }

        if (!empty($insertBatch)) {
            \App\Models\Player::upsert($insertBatch, ['external_id'], ['name', 'team_id', 'position', 'image', 'nationality', 'player_rating', 'updated_at']);
        }

        Log::info('All players fetched and inserted/updated successfully.');
    }
}
