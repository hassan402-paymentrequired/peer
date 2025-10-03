<?php

namespace App\Jobs;

use App\Models\League;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FetchLeagues implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public ?string $country = '', public ?string $id = '')
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $country = $this->country;
        $id = $this->id;
        $page = 1;
        $totalPages = 1;

        do {
            Log::info("Fetching page $page...");

            $response = Http::withHeaders([
                'x-rapidapi-key' => env('SPORT_API_KEY')
            ])->get("https://v3.football.api-sports.io/leagues?id=2");

            $body = $response->json();

            // dd($body);

            Log::info("Fetching page $response...");

            $totalPages = $body['paging']['total'] ?? 1;
            $leagues = $body['response'] ?? [];
            $count = count($leagues);
            // dd($count);
            Log::info("Total league $count...");

            foreach ($leagues as $item) {
                $l =  League::updateOrCreate(
                    [
                        'external_id' => $item['league']['id'],
                    ],
                    [
                        'name'         => $item['league']['name'],
                        'type'         => $item['league']['type'],
                        'logo'         => $item['league']['logo'],
                        'country'      => $item['country']['name'],
                        'country_flag' => $item['country']['flag'] ?? '',
                        'season'       => json_encode($item['seasons']),
                        'updated_at'   => now(),
                    ]
                );

                foreach ($item['seasons'] as $season) {
                    if ($season['current']) {
                        $l->update(['current_season' => $season]);
                        break;
                    }
                }
            }

            $page++;
        } while ($page <= $totalPages);

        Log::info('All leagues fetched and upserted successfully.');
    }
}
