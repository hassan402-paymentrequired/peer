<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\FetchLiveStatisticsJob;
use App\Jobs\CalculateCompetitionScoresJob;
use App\Models\Tournament;
use App\Models\Peer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ScoringController extends Controller
{
    public function fetchLiveStatistics(): JsonResponse
    {
        try {
            FetchLiveStatisticsJob::dispatch();

            return response()->json([
                'message' => 'Live statistics fetch job dispatched successfully',
                'status' => 'success'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to dispatch job: ' . $e->getMessage(),
                'status' => 'error'
            ], 500);
        }
    }

    public function calculateTournamentScores(Request $request): JsonResponse
    {
        $request->validate([
            'tournament_id' => 'required|exists:tournaments,id'
        ]);

        try {
            $tournament = Tournament::findOrFail($request->tournament_id);

            CalculateCompetitionScoresJob::dispatch('tournament', $tournament->id);

            return response()->json([
                'message' => "Tournament scoring job dispatched for tournament: {$tournament->name}",
                'status' => 'success'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to dispatch tournament scoring: ' . $e->getMessage(),
                'status' => 'error'
            ], 500);
        }
    }

    public function calculatePeerScores(Request $request): JsonResponse
    {
        $request->validate([
            'peer_id' => 'required|exists:peers,id'
        ]);

        try {
            $peer = Peer::findOrFail($request->peer_id);

            CalculateCompetitionScoresJob::dispatch('peer', $peer->id);

            return response()->json([
                'message' => "Peer scoring job dispatched for peer: {$peer->name}",
                'status' => 'success'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to dispatch peer scoring: ' . $e->getMessage(),
                'status' => 'error'
            ], 500);
        }
    }

    public function getCompetitionStatus(): JsonResponse
    {
        try {
            $activeTournaments = Tournament::where('status', 'open')->count();
            $activePeers = Peer::where('status', 'open')->count();
            $completedTournaments = Tournament::where('status', 'close')->count();
            $completedPeers = Peer::where('status', 'finished')->count();

            return response()->json([
                'active_tournaments' => $activeTournaments,
                'active_peers' => $activePeers,
                'completed_tournaments' => $completedTournaments,
                'completed_peers' => $completedPeers,
                'status' => 'success'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to get competition status: ' . $e->getMessage(),
                'status' => 'error'
            ], 500);
        }
    }
}
