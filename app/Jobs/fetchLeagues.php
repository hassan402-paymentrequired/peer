<?php

namespace App\Jobs;

use App\Models\League;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class fetchLeagues implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public string $country)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $country = $this->country;
        $page = 1;
        $totalPages = 1;
        $insertBatch = [];

        do {
            Log::info("Fetching page $page...");

            $params = [
                'season' => 2023,
            ];


            $response = Http::withHeaders([
                'x-rapidapi-key' => env('SPORT_API_KEY')
            ])->get("https://v3.football.api-sports.io/leagues", [
                'country' => strtolower($country ?? ''),
            ]);

            $body = $response->json();

            Log::info("Fetching page $response...");

            $totalPages = $body['paging']['total'] ?? 1;
            $leagues = $body['response'] ?? [];
            $count = count($leagues);
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
                    $l->seasons()->updateOrCreate(
                        [
                            'external_id'      => $season['year'] ?? now()->year,
                        ],
                        [
                            'is_current' => $season['current'] ?? false,
                            'start_date' => $season['start'] ?? null,
                            'end_date'   => $season['end'] ?? null,
                            'year'   => $season['year'] ?? null,
                        ]
                    );
                }
            }

            $page++;
        } while ($page <= $totalPages);

        Log::info('All leagues fetched and upserted successfully.');
    }
}
