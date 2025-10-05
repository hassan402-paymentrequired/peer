<?php

namespace App\Http\Controllers\Api\Leagues;

use App\Http\Controllers\Controller;
use App\Jobs\FetchLeagues;
use App\Models\League;
use Illuminate\Http\Request;

class LeagueController extends Controller
{
    public function index(Request $request)
    {
        $leagues = League::query()
            ->when($request->status, function ($query) use ($request) {
                return $query->where('status', $request->status);
            })
            ->orderBy('status', 'desc')
            ->paginate(20);

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

        FetchLeagues::dispatch(strtolower($name));

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

    public function update(Request $request, League $league)
    {
        $request->validate([
            'status' => 'required|in:1,0'
        ]);

        $league->update(['status' => $request->status]);

        return $this->respondWithCustomData(
            [
                'message' => 'Countries refetched successfully'
            ]
        );
    }

    public function activeCountry()
    {

        $leagues = League::active()->get();

        return $this->respondWithCustomData(
            [
                'leagues' => $leagues
            ]
        );
    }
}
