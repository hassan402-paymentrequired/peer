<?php

namespace App\Http\Controllers\V1\Player;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePlayerRequest;
use App\Jobs\ImportPlayersAndTeams;
use App\Models\League;
use App\Models\Player;
use App\Models\Team;
use App\Utils\Helper\HelperService;
use App\Utils\Service\V1\Player\PlayerService;
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
    public function store(StorePlayerRequest $request): JsonResponse
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
        $league = $request->league;
        Artisan::call('fetch:players', ['league' => $league]);
        return $this->respondWithCustomData([
            'message' => 'Players refetched successfully'
        ], 200);
    }
















    private function getLeague()
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://v3.football.api-sports.io/league?season=2023',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'x-rapidapi-key: ' . env('SPORT_API_KEY')
            ),
        ));
        $response = curl_exec($curl);
        $response = json_decode($response);
        curl_close($curl);
        return $response;
    }

    public function football()
    {
        $re = $this->getLeague();

        return response()->json(['data' => $re->result]);


        ImportPlayersAndTeams::dispatch();
        return response()->json(['data' => 'suucess']);

        $res = HelperService::getAllPlayerAndTeams();

        foreach ($res['teams']->response as  $value) {
            Team::create([
                'name' => $value->team->name,
                'external_id' => $value->team->id,
                'code' => $value->team->code,
                'country' => $value->team->country,
                'logo' => $value->team->logo
            ]);
            sleep(1);
        }

        foreach ($res['players'] as  $value) {
            Player::create([
                'name' => $value->player->name,
                'external_id' => $value->player->id,
                'team' => $value->statistics->team->name,
                'position' => $value->statistics->games->position,
                'image' => $value->player->photo,
                'nationality' => $value->player->nationality
            ]);
            sleep(1);
        }
    }
}
