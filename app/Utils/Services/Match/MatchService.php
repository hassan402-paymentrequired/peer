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
        $matches = Cache::remember(
            CacheKey::MATCH->value,
            now()->addDay(),
            function () {
                return PlayerMatch::with(
                    ['player' => function ($query) {
                        return $query->with('team');
                    }, 'team', 'fixture']
                )->get();
            }
        );

        $team = Team::select('id', 'name')->get();
        $leagues = League::select('id', 'name')->limit(50)->get();
        $groupedMatches = $matches->groupBy('fixture.league.name');
        return [$groupedMatches, $team, $leagues];
    }
}
