<?php

namespace App\Utils\Services\Player;

use App\Enum\CacheKey;
use App\Models\League;
use App\Models\Player;
use App\Models\PlayerMatch;
use App\Models\Team;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class PlayerService
{
    public function uploadPlayer(Request $request): void
    {
        Player::create($request->all());
    }

    public function players(Request $request): LengthAwarePaginator|Collection
    {
        // $players = Cache::tags(CacheKey::PLAYER->value)->remember('page_' . $request->query('page'), now()->addDay(), function () use ($request) {
        //     return ;
        // });

      $players =  Player::when(
                $request->query('team'),
                fn($query, $teamId) => $query->whereHas('team', fn($q) => $q->where('id', $teamId))
            )->with('team')->orderBy('status')->paginate(30);

        return $players;
    }

    public function updatePlayer(Request $request, Player $player): void
    {
        $player->update($request->all());
    }

    public function deletePlayer(Player $player): void
    {
        $player->delete();
    }

    public function player(string $player_id): Player
    {
        return Player::findOrFail($player_id);
    }

    public function processMatch(Team $team, Request $request, League $league): void
    {
        foreach ($request->playerIds as $id) {
            PlayerMatch::create([
                'player_id' => $id,
                'team_id' => $team->id,
                'date' => $request->date,
                'time' => $request->time,
                'league_id' => $league->id
            ]);
        }
    }




    public function groupedByStar()
    {
        $matches = PlayerMatch::with(['player', 'team'])
            ->where('is_completed', false)
            ->orderBy('date')
            ->get();

        // Group by player star rating
        $grouped = $matches->groupBy(function ($match) {
            return $match->player->player_rating;
        });

        // Format the response
        $players = $grouped->map(function ($matches, $star) {
            return [
                'star' => (int) $star,
                'players' => $matches->map(function ($match) {
                    return [
                        'player_avatar' => $match->player->image,
                        'player_position' => $match->player->position,
                        'player_external_id' => $match->player->external_id,
                        'player_match_id' => $match->id,
                        'player_id' => $match->player_id,
                        'player_team' => $match->player->team->name,
                        'against_team_image' => $match->team->logo,
                        'player_name' => $match->player->name,
                        'against_team_name' => $match->team->name,
                        'date' => $match->date,
                        'time' => $match->time,
                    ];
                })->values()
            ];
        })->values();

        return $players;
    }
}
