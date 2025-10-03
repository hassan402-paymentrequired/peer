<?php

namespace App\Http\Controllers\Api\Team;

use App\Jobs\FetchTeams;
use Illuminate\Http\Request;
use App\Models\Team;
use App\Utils\Services\Team\TeamService;

class TeamController extends \App\Http\Controllers\Controller
{

    protected TeamService $teamService;

    public function __construct(TeamService $teamService)
    {
        $this->teamService = $teamService;
    }

    public function index()
    {
        return $this->teamService->teams();
    }

    public function refetch(Request $request)
    {
        $request->validate([
            'league_id' => ['required']
        ]);
        $league = $request->league_id;
        FetchTeams::dispatch($league);
        return $this->respondWithCustomData(
            [
                'message' => 'Teams refetched successfully'
            ]
        );
    }

    /**
     * Update the status of a team (admin only)
     */
    public function updateStatus(Request $request, Team $team)
    {
        $request->validate([
            'status' => 'required|in:1,0',
        ]);
        $team->status = $request->status;
        $team->save();
        return $this->respondWithCustomData([
            'message' => 'Team status updated successfully'
        ]);
    }

    /**
     * View all players of a team
     */
    public function players(string $team_id)
    {
        $team = Team::where('external_id', $team_id)->orWhere('id', $team_id)->firstOrFail();

        $players = $team->players;
        return $this->respondWithCustomData([
            'players' => $players
        ], 200);
    }
}
