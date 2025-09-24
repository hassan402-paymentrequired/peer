<?php

namespace App\Utils\Services\Peer;

use App\Enum\PeerShareRatioEnum;
use App\Models\Peer;
use App\Utils\Helper\HelperService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Enum\CacheKey;
use App\Exceptions\ClientErrorException;
use App\Models\PeerUser;
use App\Models\PeerUserSquad;
use App\Models\PeerUserSquard;
use App\Models\Player;
use App\Models\PlayerMatch;
use App\Models\PlayerMatchStatistics;
use Illuminate\Support\Facades\DB;

class PeerService
{
    public function getPeers()
    {
        $page = request('page', 1);
        $ttl = now()->addHours(4);

        return Cache::remember(CacheKey::PEERS->value . "_page_{$page}", $ttl, function () {
            return Peer::with('created_by')->withCount('users')->paginate(20);
        });
    }


    public function createPeer(Request $request): JsonResponse|Peer
    {
        $peer = Peer::where('user_id', Auth::id())->latest()->first();
        if ($peer) {
            if ($peer->users()->count() === 0) {
                return HelperService::returnWithError('You already have a peer with no users');
            }
        }

        $peer = Peer::create([
            'name' => $request->name,
            'amount' => $request->amount,
            'private' => $request->private,
            'limit' => $request->limit,
            'user_id' => Auth::guard('api')->id(),
            'sharing_ratio' => $request->ratio ??= PeerShareRatioEnum::DIVIDE->value
        ]);

        $peer->addUser(Auth::id());
        return $peer;
    }

    public function updatePeer(Request $request, Peer $peer): void
    {
        $peer->update($request->all());
    }

    public function deletePeer(Peer $peer): void
    {
        $peer->delete();
    }

    public function playBet(Request $request, Peer $peer, string $guard = WEB): bool
    {
        // Check if peer is full
        if ($peer->users()->count() === $peer->limit) {
            return false;
        }

        // Check if user already joined
        if ($peer->users()->where('user_id', AuthUser($guard)->id)->exists()) {
            return false;
        }

        // Create peer_user record
        $peerUser = \App\Models\PeerUser::create([
            'peer_id' => $peer->id,
            'user_id' => AuthUser($guard)->id,
            'total_points' => 0,
            'is_winner' => false
        ]);

        // Create peer_user_squad records for each squad member
        foreach ($request->peers as $value) {
            \App\Models\PeerUserSquard::create([
                'peer_user_id' => $peerUser->id,
                'star_rating' => $value['star'] ?? 1,
                'main_player_id' => $value['main'],
                'sub_player_id' => $value['sub'],
                'main_player_match_id' => $value['main_player_match_id'],
                'sub_player_match_id' => $value['sub_player_match_id'],
            ]);
        }
        return true;
    }



    public function join(Request $request, Peer $peer)
    {
        $user = auth('api')->user();

        // Check if peer is open
        if ($peer->status !== 'open') {
            return response()->json(['message' => 'This peer is not open for joining.'], 403);
        }

        // Check if user already joined
        if ($peer->users()->where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'You have already joined this peer.'], 403);
        }

        // Validate squad
        $validated = $request->validate([
            'squad' => 'required|array|size:5',
            'squad.*.star' => 'required|integer|between:1,5',
            'squad.*.main_player_id' => 'required|exists:players,id',
            'squad.*.sub_player_id' => 'required|exists:players,id|different:squad.*.main_player_id',
        ]);

        DB::beginTransaction();

        try {
            // Create peer_user
            $peerUser = PeerUser::create([
                'peer_id' => $peer->id,
                'user_id' => $user->id,
                'total_points' => 0,
                'is_winner' => false
            ]);

            // Create squad
            foreach ($validated['squad'] as $item) {
                PeerUserSquard::create([
                    'peer_user_id' => $peerUser->id,
                    'star_rating' => $item['star'],
                    'main_player_id' => $item['main_player_id'],
                    'sub_player_id' => $item['sub_player_id'],
                ]);
            }

            DB::commit();

            return response()->json(['message' => 'Successfully joined the peer.'], 201);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['message' => 'Something went wrong', 'error' => $e->getMessage()], 500);
        }
    }
}
