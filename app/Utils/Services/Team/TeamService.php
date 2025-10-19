<?php

namespace App\Utils\Services\Team;

use App\Enum\CacheKey;
use App\Models\Team;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class TeamService
{
    public function teams($request)
    {
        return Team::when($request->search, function ($query) use ($request) {
            $query->where('name', 'like', '%' . $request->search . '%');
        })->withCount('players')->orderBy('status', 'desc')->paginate(20);
    }

    public function activeTeams()

    {
        // return Cache::remember('teams_page_' . request('page'), 300, function () {
        return Team::where('status', true)->get();
        // });
    }
}
