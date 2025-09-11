<?php

namespace App\Http\Controllers\Peer;

use App\Http\Controllers\Controller;
use App\Models\Peer;
use App\Models\Tournament;
use App\Utils\Services\Player\PlayerService;
use Inertia\Inertia;

class PeerController extends Controller
{
    protected PlayerService $playerService;

    public function __construct(PlayerService $playerService) {
        $this->playerService = $playerService;
    }

    public function index()
    {
        $user = authUser();
        $today = now()->toDateString();
        // $tournament = Tournament::whereDate('created_at', $today)->first();
        $tournament = Tournament::where('status', 'open')->first();
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
}
