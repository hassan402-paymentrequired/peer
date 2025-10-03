<?php

namespace App\Http\Controllers\Api\Fixture;

use App\Http\Controllers\Controller;
use App\Jobs\fetchWeeklyFixtures;
use App\Models\Fixture;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class FixtureController extends Controller
{
    public function index()
    {
        return $this->respondWithCustomData([
            'fixtures' => Fixture::all()
        ], 200);
    }

    public function refetch(Request $request)
    {
        $league = $request->league ?? '2';
        $season = $request->season ?? '2025';
        $from = Carbon::now()->format('Y-m-d');
        $to = Carbon::now()->addDays(7)->format('Y-m-d');


        fetchWeeklyFixtures::dispatch($league, $season, $to, $from);

        return $this->respondWithCustomData([
            'message' => 'Fixtures refetched successfully'
        ], 200);
    }
}
