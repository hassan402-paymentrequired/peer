<?php

namespace App\Utils\Services\Match;

use App\Enum\CacheKey;
use App\Models\League;
use App\Models\PlayerMatch;
use App\Models\Team;
use Illuminate\Support\Facades\Cache;

class MatchService
{
    public function matches(): array
    {

        // $matches = PlayerMatch::with(
        //     ['player' => function ($query) {
        //         return $query->with('team');
        //     }, 'team', 'fixture']
        // )->orderBy('created_at', 'desc')->paginate(5);


        // $team = Team::select('id', 'name')->get();
        // $leagues = League::select('id', 'name')->limit(50)->get();
        // $groupedMatches = $matches->groupBy('fixture.league.name');
        // return [$groupedMatches, $team, $leagues];

        $matches = PlayerMatch::with([
            'player.team',
            'team',
            'fixture.league'
        ])->orderBy('created_at', 'desc')->paginate(5);

        $groupedMatches = $matches->getCollection()->groupBy('fixture.league.name');

        return [
            'data' => $groupedMatches,
            'pagination' => [
                'current_page' => $matches->currentPage(),
                'last_page' => $matches->lastPage(),
                'per_page' => $matches->perPage(),
                'total' => $matches->total(),
            ],
            'teams' => Team::select('id', 'name')->get(),
            'leagues' => League::select('id', 'name')->limit(50)->get(),
        ];
    }
}
