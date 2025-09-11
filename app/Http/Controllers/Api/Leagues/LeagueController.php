<?php

namespace App\Http\Controllers\Api\Leagues;

use App\Http\Controllers\Controller;
use App\Models\League;
use App\Models\Season;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class LeagueController extends Controller
{
    public function index(Request $request)
    {
        $leagues = League::with(['seasons' => function ($query) {
            $query->where('is_current', true);
        }])
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
        $id = $request->country_id ?? '';

        Artisan::call('fetch:leagues', ['country' => $id]);

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
