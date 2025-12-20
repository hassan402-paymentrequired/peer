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
    public function __construct(public string $league,  public string $year = YEAR)
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
            // $response = \Illuminate\Support\Facades\Http::withHeaders([
            //     'x-rapidapi-key' => $apiKey
            // ])->get($apiUrl, [
            //     'league' => $leagueId,
            //     'season' => $season,
            //     'page' => $page
            // ]);
            $players = players_data(39, YEAR);


            // $body = $response->json();
            // $players = $body['response'] ?? [];
            // $paging = $body['paging'] ?? ['current' => $page, 'total' => $page];
            // $currentPage = $paging['current'] ?? $page;
            // $totalPages = $paging['total'] ?? $page;

            Log::info("Total players fetched: " . count($players));

            foreach ($players as $item) {
                $player = $item['player'];
                $stats = $item['statistics'][0] ?? [];
                $team = $stats['team'] ?? [];
                $games = $stats['games'] ?? [];
                $position = $games['position'] ?? '';

                \App\Models\Player::query()->updateOrCreate(
                    [
                        'external_id' => $player['id'],
                        'team_id' => $team['id']
                    ],
                    [
                        'name' => $player['name'],
                        'team_id' => $team['id'] ?? '',
                        'position' => $position,
                        'image' => $player['photo'] ?? '',
                        'nationality' => $player['nationality'] ?? '',
                        'player_rating' => random_int(1, 5),
                    ]
                );
            }

            // if ($currentPage >= $totalPages) {
            //     break;
            // }
            // $page++;
            break;
        }

        Log::info('All players fetched and inserted/updated successfully.');
    }

    function call_api($endpoint, $params = [])
    {

        $parameters = '';
        if (count($params) > 0) {
            $parameters = '?' . http_build_query($params);
        }

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://v3.football.api-sports.io/' . $endpoint . $parameters,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'x-rapidapi-key: '. env('SPORT_API_KEY')
            ),
        ));
        $response = curl_exec($curl);
        $response = json_decode($response);
        curl_close($curl);
        return $response;
    }

    function players_data($league, $season, $page = 1, $players_data = [])
    {

        $players = call_api('players', ['league' => $league, 'season' => $season, 'page' => $page]);
        $players_data = array_merge($players_data, $players->response);

        if ($players->paging->current < $players->paging->total) {

            $page = $players->paging->current + 1;
            if ($page % 2 == 1) {
                sleep(1);
            }
            $players_data = players_data($league, $season, $page, $players_data);
        }
        return $players_data;
    }
}
