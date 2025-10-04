<?php

namespace App\Http\Controllers\Player;

use App\Http\Controllers\Controller;
use App\Http\Requests\Player\StorePlayerRequest as PlayerStorePlayerRequest;
use App\Http\Requests\StorePlayerRequest;
use App\Jobs\fetchPlayers;
use App\Models\League;
use App\Models\Player;
use App\Models\Team;
use App\Utils\Helper\HelperService;
use App\Utils\Services\Player\PlayerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class PlayerController extends Controller
{
    protected PlayerService $playerService;

    public function __construct(PlayerService $playerService)
    {
        $this->playerService = $playerService;
    }
    public function store(PlayerStorePlayerRequest $request): JsonResponse
    {
        $this->playerService->uploadPlayer($request);
        return $this->respondWithCustomData([
            'message' => 'player uploaded successfully'
        ], 200);
    }

    public function index(Request $request): JsonResponse
    {
        return $this->respondWithCustomData([
            'players' => $this->playerService->players($request)
        ], 200);
    }

    public function show(Player $player): JsonResponse
    {
        return $this->respondWithCustomData([
            'player' => $player
        ], 200);
    }

    public function update(Request $request, Player $player): JsonResponse
    {
        $this->playerService->updatePlayer($request, $player);
        return $this->respondWithCustomData([
            'message' => 'player updated successfully'
        ], 200);
    }

    public function destroy(Player $player): JsonResponse
    {
        $this->playerService->deletePlayer($player);
        return $this->respondWithCustomData([
            'message' => 'player deleted successfully'
        ], 200);
    }

    public function updatePlayerStar(Player $player, Request $request): JsonResponse
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5'
        ]);
        $player->update([
            'player_rating' => $request->rating
        ]);
        return $this->respondWithCustomData([
            'message' => 'player star updated successfully'
        ], 200);
    }

    public function createMatch(Request $request, Team $team, League $league): JsonResponse
    {

        $request->validate([
            'date' => 'required|date',
            'time' => 'required|date_format:H:i',
            'playerIds' => 'required|array',
            'playerIds.*' => 'exists:players,id',
        ]);
        $this->playerService->processMatch($team, $request, $league);
        return $this->respondWithCustomData([
            'message' => 'match setup successfully'
        ], 200);
    }

    public function teamPlayers(Team $team): JsonResponse
    {
        $players = $team->players()->with('team')->get();
        return $this->respondWithCustomData([
            'players' => $players
        ], 200);
    }

    public function getPlayersByStar()
    {
        $starPlayers = $this->playerService->groupedByStar();
        return $this->respondWithCustomData([
            'players' => $starPlayers
        ], 200);
    }




    public function refetch(Request $request)
    {
        $request->validate([
            'league_id' => ['required']
        ]);
        $league = $request->league_id;
        fetchPlayers::dispatch($league);
        return $this->respondWithCustomData([
            'message' => 'Players refetched successfully'
        ], 200);
    }


}
