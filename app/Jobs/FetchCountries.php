<?php

namespace App\Jobs;

use App\Models\Country;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FetchCountries implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $apiUrl = 'https://v3.football.api-sports.io/countries';
        $page = 1;
        $totalPages = 1;
        $updated = 0;
        Log::info("Fetting started.");
        do {
            $response = Http::withHeaders([
                'x-rapidapi-key' => config('app.football.api_key'),
            ])->get('https://v3.football.api-sports.io/countries?');

            if (!$response->ok()) {
                Log::error('Failed to fetch countries: ' . $response->body());
            }

            $data = $response->json();
            $countries = $data['response'] ?? [];
            $paging = $data['paging'] ?? ['current' => $page, 'total' => $page];
            $totalPages = $paging['total'] ?? 1;


            foreach ($countries as $country) {
                Country::updateOrCreate(
                    [
                        'code' => $country['code'],
                    ],
                    [
                        'name' => $country['name'],
                        'flag' => $country['flag'] ?? null,
                        'external_id' => $country['id'] ?? null,
                    ]
                );
                $updated++;
            }

            Log::info("Fetched page $page of $totalPages, updated $updated countries so far.");
            $page++;
        } while ($page <= $totalPages);

        Log::info('Countries fetch complete.');
    }
}
