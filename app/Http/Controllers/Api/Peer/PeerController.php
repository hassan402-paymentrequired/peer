<?php

namespace App\Http\Controllers\Api\Peer;

use App\Http\Controllers\Controller;
use App\Http\Requests\BetRequest;
use App\Http\Requests\StorePeerRequest;
use App\Models\Peer;
use App\Utils\Helper\HelperService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Enum\CacheKey;
use App\Http\Requests\Peer\StorePeerRequest as PeerStorePeerRequest;
use App\Utils\Services\Peer\PeerService;
use Illuminate\Container\Attributes\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log as FacadesLog;

class PeerController extends Controller
{
    protected PeerService $peerService;

    public function __construct(PeerService $peerService)
    {
        $this->peerService = $peerService;
    }

    public function index(): JsonResponse
    {
        $peers = $this->peerService->getPeers();
        return $this->respondWithCustomData([
           'peers' => $peers
        ], 200);
    }

    public function store(PeerStorePeerRequest $request): JsonResponse
    {
        $peer = $this->peerService->createPeer($request);
        return $this->respondWithCustomData([
            'message' => 'peer created successfully',
            'peer' => $peer
        ], 200);
    }

    public function show1(Peer $peer): JsonResponse
    {
        $peer = Cache::remember(CacheKey::PEERS->value . $peer->id, now()->addHours(1), function () use ($peer) {
            return $peer->load(['users', 'created_by']);
        });
        return $this->respondWithCustomData([
            'peer' => $peer
        ], 200);
    }



    public function update(Request $request, Peer $peer): JsonResponse
    {
        $this->peerService->updatePeer($request, $peer);
        return $this->respondWithCustomData([
            'message' => 'peer updated successfully'
        ], 200);
    }

    public function destroy(Peer $peer): JsonResponse
    {
        if ($peer->users()->count() > 1) {
            return $this->respondWithCustomData([
                'message' => 'peer cannot be deleted because it has users'
            ], 400);
        }
        $this->peerService->deletePeer($peer);
        return $this->respondWithCustomData([
            'message' => 'peer deleted successfully'
        ], 200);
    }

   

   

    /**
     * Return all peers the authenticated user belongs to
     */
    public function myPeers(): JsonResponse
    {
        $userId = Auth::guard('api')->id();
        $peerUsers = \App\Models\PeerUser::with(['peer', 'squads'])
            ->where('user_id', $userId)
            ->get();

        $peers = $peerUsers->map(function ($peerUser) {
            return [
                'peer' => [
                    'id' => $peerUser->peer->id,
                    'name' => $peerUser->peer->name,
                    'status' => $peerUser->peer->status,
                    'entry_fee' => $peerUser->peer->amount,
                ],
                'total_points' => $peerUser->total_points,
                'is_winner' => $peerUser->is_winner,
                'squad' => $peerUser->squads->map(function ($squad) {
                    return [
                        'star' => $squad->star_rating,
                        'main_player_id' => $squad->main_player_id,
                        'sub_player_id' => $squad->sub_player_id,
                    ];
                }),
            ];
        });

        return $this->respondWithCustomData([
            'peers' => $peers
        ], 200);
    }

    /**
     * Return all ongoing (open) peers the authenticated user belongs to
     */
    public function myOngoingPeers(): JsonResponse
    {
        $userId = Auth::guard('api')->id();

        $peers = Auth::guard('api')->user()->peers()
            ->where('status', 'open')
            ->with('created_by')
            ->withCount('users')
            ->get();

        return $this->respondWithCustomData([
            'peers' => $peers
        ], 200);
    }

    /**
     * Return all completed (finished/closed) peers the authenticated user belongs to
     */
    public function myCompletedPeers(): JsonResponse
    {
        $userId = Auth::guard('api')->id();
        $peerUsers = \App\Models\PeerUser::with('peer')
            ->where('user_id', $userId)
            ->whereHas('peer', function ($q) {
                $q->whereIn('status', ['finished', 'closed']);
            })
            ->get();

        $peers = $peerUsers->map(function ($peerUser) {
            $peer = $peerUser->peer;
            return [
                'id' => $peer->id,
                'name' => $peer->name,
                'status' => $peer->status,
                'entry_fee' => $peer->amount,
                'total_users' => $peer->users()->count(),
            ];
        });

        return $this->respondWithCustomData([
            'peers' => $peers
        ], 200);
    }


}
