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
        $league = $request->league ?? '39';
        $season = $request->season ?? '2023';
        $from = $request->from ?? '2021-07-01';
        $to = $request->to ?? '2023-10-31';


        fetchWeeklyFixtures::dispatch($league, $season, $to, $from);

        return $this->respondWithCustomData([
            'message' => 'Fixtures refetched successfully'
        ], 200);
    }
}
