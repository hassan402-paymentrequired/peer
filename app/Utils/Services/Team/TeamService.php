<?php

namespace App\Utils\Services\Team;

use App\Enum\CacheKey;
use App\Models\Team;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class TeamService
{
    public function teams()
    {
        return Cache::remember('teams_page_' . request('page'), 300, function () {
            return Team::where('status', true)->withCount('players')->paginate(20);
        });
    }
}
