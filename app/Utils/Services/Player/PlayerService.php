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

        $players =  Player::query()
            ->when(
                $request->query('team'),
                fn($query, $teamId) => $query->whereHas('team', fn($q) => $q->where('id', $teamId))
            )->when($request->search, function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->search . '%');
            })->with('team')->orderBy('status', 'desc')->paginate(30);

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
        $matches = PlayerMatch::with([
            'player',
            'team',
            'fixture',
        ])
            ->where('is_completed', false)
            ->whereHas('fixture', function ($query) {
                $query->where('date', '>=', now()->subHours(2))
                    ->where('date', '<=', now()->addHours(24))
                    // Only include fixtures that haven't started yet
                    ->where('status', 'Not Started');
            })
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
                    $fixture = $match->fixture;
                    $player = $match->player;

                    return [
                        'player_avatar' => $player->image,
                        'player_position' => $player->position,
                        'player_external_id' => $player->external_id,
                        'player_match_id' => $match->id,
                        'player_id' => $match->player_id,

                        'player_team' => $match->player->team->name,
                        'against_team_image' => $match->team->logo,
                        'player_name' => $match->player->name,
                        'against_team_name' => $match->team->name,
                        'date' => $match->date,
                        'time' => $match->time,


                        'fixture_status' => $fixture->status,
                    ];
                })->values()
            ];
        })
            // Filter out star groups that have no available players
            ->filter(function ($group) {
                return $group['players']->isNotEmpty();
            })
            ->values();

        return $players;
    }
}
