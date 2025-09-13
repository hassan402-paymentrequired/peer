<?php

namespace App\Utils\Services\Team;

use App\Enum\CacheKey;
use App\Models\Team;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class TeamService
{
    public function teams(): Collection
    {
        return Cache::remember('teams', now()->addDay(), function () {
            return Team::where('status', 1)->withCount('players')->get();
        });
    }
}
