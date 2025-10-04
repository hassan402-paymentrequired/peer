<?php

namespace App\Http\Controllers\Peer;

use App\Enum\PeerShareRatioEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Peer\BetRequest;
use App\Http\Requests\Peer\StorePeerRequest;
use App\Models\Peer;
use App\Models\Tournament;
use App\Utils\Services\Peer\PeerService;
use App\Utils\Services\Player\PlayerService;
use Inertia\Inertia;

class PeerController extends Controller
{
    protected PlayerService $playerService;
    protected PeerService $peerService;

    public function __construct(PlayerService $playerService, PeerService $peerService)
    {
        $this->playerService = $playerService;
        $this->peerService = $peerService;
    }

    public function index()
    {
        $user = authUser();
        $tournament = Tournament::active()->first();
        $recent = Peer::with('created_by')
            ->whereDoesntHave('users', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->withCount('users')
            ->latest()
            ->take(4)
            ->get();

        // dd($tournament);

        $peers = Peer::with('created_by')->whereDoesntHave('users', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->withCount('users')->latest()->paginate(10);

        return Inertia::render('dashboard', [
            'tournament' => $tournament,
            'recents' => $recent,
            'peers' => $peers,
        ]);
    }


    public function joinPeer(Peer $peer)
    {
        // dd($peer);
        $players = $this->playerService->groupedByStar();
        // Log::info($players->toArray());
        $peer = $peer->loadCount('users');
        return Inertia::render('peer/join', [
            'peer' => $peer,
            'players' => $players,
            'balance' => optional(request()->user())->load('wallet')->balance
        ]);
    }

    public function create()
    {
        return Inertia::render('peer/create', [
            'user' => authUser()->load('wallet')
        ]);
    }

    public function store(StorePeerRequest $request)
    {
        $peer = Peer::where('user_id', authUser()->id)->latest()->first();
        if ($peer) {
            if ($peer->users()->count() === 0) {
                return back()->with('error', 'You already have an active peer with no users');
            }
        }

        $user = AuthUser('web');

        $peer = Peer::create([
            'name' => $request->name,
            'amount' => $request->amount,
            'private' => $request->private,
            'limit' => $request->limit,
            'user_id' => $user->id,
            'sharing_ratio' => $request->sharing_ratio === '1' ? PeerShareRatioEnum::ALL->value : PeerShareRatioEnum::DIVIDE->value,
        ]);

        // $peer->addUser(AuthUser('web')->id);

        decreaseWallet($peer->amount, 'web');

        return to_route('join.peer', [
            'peer' => $peer->id
        ])->with('success', 'Peer created successfully. You can now select your squard');
    }

    public function myGame()
    {
        $user = authUser();

        // Ongoing: Peers the user joined that are currently ongoing (paginated)
        $ongoingPeers = $user->peers()
            ->where('status', 'open')
            ->with(['created_by', 'winner'])
            ->withCount('users')
            ->latest()
            ->paginate(10, ['*'], 'ongoing_page');

        // History: Completed peers (paginated)
        $historyPeers = $user->peers()
            ->whereIn('status', ['finished', 'closed'])
            ->with(['created_by', 'winner'])
            ->withCount('users')
            ->latest('updated_at')
            ->paginate(10, ['*'], 'history_page');

        return Inertia::render('peer/contests', [
            'ongoing' => $ongoingPeers,
            'history' => $historyPeers,
        ]);
    }



    public function storeJoinPeer(BetRequest $request, Peer $peer)
    {
        $user = authUser()->id;
        if (!hasEnoughBalance($peer->amount) && $peer->user_id !== $user) {
            return back()->with('error', 'Insufficient balance to join peer. Please fund your wallet.');
        }
        $result = $this->peerService->playBet($request, $peer, WEB);

        if (!$result) {
            return back()->with('error', 'You are already in the peer');
        }

        if ($peer->user_id !== $user) {
            decreaseWallet($peer->amount);
        }

        return to_route('peers.show', [
            'peer' => $peer
        ])->with('success', 'Peer joined successfully');
    }


    public function show(Peer $peer)
    {
        $peer = \App\Models\Peer::with('created_by')->findOrFail($peer->id);

        // Get all users who joined this peer, with their squads
        $peerUsers = \App\Models\PeerUser::with(['user', 'squads.mainPlayer', 'squads.subPlayer'])->where('peer_id', $peer->id)->get();

        $users = $peerUsers->map(function ($peerUser) {
            $user = $peerUser->user;
            $squads = $peerUser->squads->map(function ($squad) {
                // Get fixture_id for main and sub from player_match
                $mainPlayerMatch = \App\Models\PlayerMatch::find($squad->main_player_match_id);
                $main_fixture_id = $mainPlayerMatch ? $mainPlayerMatch->fixture_id : null;
                $subPlayerMatch = \App\Models\PlayerMatch::find($squad->sub_player_match_id);
                $sub_fixture_id = $subPlayerMatch ? $subPlayerMatch->fixture_id : null;

                // Get main player stats using player_id and fixture_id
                $mainStats = null;
                if ($main_fixture_id) {
                    $mainStats = \App\Models\PlayerStatistic::where('player_id', $squad->main_player_id)
                        ->where('fixture_id', $main_fixture_id)
                        ->first();
                }

                // Get sub player stats using player_id and fixture_id
                $subStats = null;
                if ($sub_fixture_id) {
                    $subStats = \App\Models\PlayerStatistic::where('player_id', $squad->sub_player_id)
                        ->where('fixture_id', $sub_fixture_id)
                        ->first();
                }

                $mainPlayer = $squad->mainPlayer ? $squad->mainPlayer->toArray() : [];
                $mainPlayer['statistics'] = $mainStats ? $mainStats->toArray() : [];

                $subPlayer = $squad->subPlayer ? $squad->subPlayer->toArray() : [];
                $subPlayer['statistics'] = $subStats ? $subStats->toArray() : [];

                return [
                    'id' => $squad->id,
                    'peer_user_id' => $squad->peer_user_id,
                    'star_rating' => $squad->star_rating,
                    'main_player_id' => $squad->main_player_id,
                    'sub_player_id' => $squad->sub_player_id,
                    'main_player_match_id' => $squad->main_player_match_id,
                    'sub_player_match_id' => $squad->sub_player_match_id,
                    'main_player' => $mainPlayer,
                    'sub_player' => $subPlayer,
                ];
            });

            return [
                'id' => $user->id,
                'username' => $user->name,
                'avatar' => $user->avatar,
                'email' => $user->email,
                'created_at' => $user->created_at,
                'squads' => $squads,
            ];
        });


        return Inertia::render('peer/show', [
            'peer' => $peer,
            'users' => $users
        ]);
    }

    /**
     * Get ongoing peers for infinite scroll (API endpoint)
     */
    public function getOngoingPeers(Request $request)
    {
        $user = authUser();
        $page = $request->get('page', 1);

        $ongoingPeers = $user->peers()
            ->where('status', 'open')
            ->with(['created_by', 'winner'])
            ->withCount('users')
            ->latest()
            ->paginate(10, ['*'], 'page', $page);

        return response()->json($ongoingPeers);
    }

    /**
     * Get completed peers for infinite scroll (API endpoint)
     */
    public function getCompletedPeers(Request $request)
    {
        $user = authUser();
        $page = $request->get('page', 1);

        $historyPeers = $user->peers()
            ->whereIn('status', ['finished', 'closed'])
            ->with(['created_by', 'winner'])
            ->withCount('users')
            ->latest('updated_at')
            ->paginate(10, ['*'], 'page', $page);

        return response()->json($historyPeers);
    }
}
