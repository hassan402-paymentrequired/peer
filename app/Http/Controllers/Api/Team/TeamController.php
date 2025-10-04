<?php

namespace App\Http\Controllers\Api\Team;

use App\Jobs\FetchTeams;
use App\Models\Player;
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
        FetchTeams::dispatch($league, date('Y'));
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
        $players = Player::where('team_id', $team_id)->get();
        return $this->respondWithCustomData([
            'players' => $players
        ], 200);
    }

    /**
     * View only players who are currently in lineups (actually playing)
     */
    public function playingPlayers(string $team_id)
    {
        // Get all players from this team who are in current lineups
        $playingPlayerIds = \App\Models\FixtureLineup::whereHas('fixture', function ($query) {
            // Only consider upcoming and ongoing fixtures
            $query->whereIn('status', [
                'Not Started',
                'First Half',
                'Second Half',
                'Halftime',
                'Extra Time',
                'Penalty In Progress'
            ])
                ->where('date', '>=', now()->subHours(3))
                ->where('date', '<=', now()->addHours(24));
        })
            ->where('team_id', $team_id)
            ->get()
            ->reduce(function ($carry, $lineup) {
                return array_merge($carry, $lineup->getAllPlayerIds());
            }, []);

        $uniquePlayingPlayerIds = array_unique($playingPlayerIds);

        // Get the actual player records
        $playingPlayers = Player::where('team_id', $team_id)
            ->whereIn('external_id', $uniquePlayingPlayerIds)
            ->where('status', true) // Only active players
            ->get();

        // Add lineup information to each player
        $playersWithLineupInfo = $playingPlayers->map(function ($player) use ($team_id) {
            $lineupInfo = $this->getPlayerLineupInfo($player->external_id, $team_id);

            return [
                'id' => $player->id,
                'external_id' => $player->external_id,
                'name' => $player->name,
                'position' => $player->position,
                'image' => $player->image,
                'nationality' => $player->nationality,
                'player_rating' => $player->player_rating,
                'status' => $player->status,
                'lineup_info' => $lineupInfo
            ];
        });

        return $this->respondWithCustomData([
            'playing_players' => $playersWithLineupInfo,
            'total_playing' => $playersWithLineupInfo->count(),
            'team_id' => $team_id
        ], 200);
    }

    /**
     * Get lineup information for a specific player
     */
    private function getPlayerLineupInfo(string $playerExternalId, string $teamId): array
    {
        $lineups = \App\Models\FixtureLineup::whereHas('fixture', function ($query) {
            $query->whereIn('status', [
                'Not Started',
                'First Half',
                'Second Half',
                'Halftime',
                'Extra Time',
                'Penalty In Progress'
            ])
                ->where('date', '>=', now()->subHours(3))
                ->where('date', '<=', now()->addHours(24));
        })
            ->where('team_id', $teamId)
            ->with('fixture')
            ->get()
            ->filter(function ($lineup) use ($playerExternalId) {
                return $lineup->hasPlayer($playerExternalId);
            });

        return $lineups->map(function ($lineup) use ($playerExternalId) {
            return [
                'fixture_id' => $lineup->fixture->external_id,
                'fixture_date' => $lineup->fixture->date,
                'fixture_status' => $lineup->fixture->status,
                'formation' => $lineup->formation,
                'is_starting' => $lineup->isPlayerStarting($playerExternalId),
                'is_substitute' => $lineup->isPlayerSubstitute($playerExternalId),
                'position_in_lineup' => $lineup->getPlayerPosition($playerExternalId)
            ];
        })->toArray();
    }
}
