<?php

namespace App\Http\Controllers\Api\Fixture;

use App\Http\Controllers\Controller;
use App\Jobs\FetchWeeklyFixtures;
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
        $league = $request->league;
        $season = $request->season ?? '2025';
        $from = Carbon::parse($request->from)->format('Y-m-d') ?? Carbon::now()->format('Y-m-d') ;
        $to = Carbon::parse($request->to)->format('Y-m-d') ?? Carbon::now()->addDays(7)->format('Y-m-d');


        FetchWeeklyFixtures::dispatch($league, $season, $to, $from);

        return $this->respondWithCustomData([
            'message' => 'Fixtures refetched successfully'
        ], 200);
    }
}
