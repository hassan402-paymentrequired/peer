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
        $key =   CacheKey::MATCH->value . '_page_' . request()->page;
        $matches = Cache::remember(
            $key,
            300,
            function () {
                return PlayerMatch::with(
                    ['player' => function ($query) {
                        return $query->with('team');
                    }, 'team', 'fixture']
                )->paginate(20);
            }
        );

        $team = Team::select('id', 'name')->get();
        $leagues = League::select('id', 'name')->limit(50)->get();
        $groupedMatches = $matches->groupBy('fixture.league.name');
        return [$groupedMatches, $team, $leagues];
    }
}
