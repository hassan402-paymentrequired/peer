<?php

namespace App\Http\Controllers\Api\Leagues;

use App\Http\Controllers\Controller;
use App\Jobs\FetchLeagues;
use App\Models\League;
use App\Models\Season;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class LeagueController extends Controller
{
    public function index(Request $request)
    {
        $leagues = League::query()
            ->when($request->status, function ($query) use ($request) {
                return $query->where('status', $request->status);
            })
            ->orderBy('status')
            ->get();

        return $this->respondWithCustomData(
            [
                'leagues' => $leagues
            ]
        );
    }

    public function show(League $league)
    {
        return $this->respondWithCustomData(
            [
                'league' => $league->load('seasons')
            ]
        );
    }

    public function refetch(Request $request)
    {
        $request->validate([
            'country_name' => ['required']
        ]);
        $name = $request->country_name ?? '';

        FetchLeagues::dispatch($name);

        return $this->respondWithCustomData(
            [
                'message' => 'Leagues refetched successfully'
            ]
        );
    }

    public function getLeagueSeason(League $league)
    {

        $season = $league->seasons()
            ->where('is_current', true)
            ->first();

        return $this->respondWithCustomData(
            [
                'seasons' => $season
            ]
        );
    }

    public function getLeagueSeasonAndRound(League $leagues)
    {
        $league = $leagues;

        // $seasons = Season::where('league_id', $league->id)
        //     ->with(['league'])
        //     ->get();

        // return $this->respondWithCustomData(
        //     [
        //         'seasons' => $seasons
        //     ]
        // );
    }
}
