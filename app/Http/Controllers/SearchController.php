<?php

namespace App\Http\Controllers;

use App\Models\Peer;
use App\Models\Tournament;
use App\Models\User;
use App\Models\Player;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->get('q', '');
        $type = $request->get('type', 'all'); // all, peers, tournaments, users, players

        if (empty($query)) {
            return Inertia::render('search/index', [
                'query' => $query,
                'type' => $type,
                'results' => [
                    'peers' => [],
                    'tournaments' => [],
                    'users' => [],
                    'players' => []
                ]
            ]);
        }

        $results = [];

        if ($type === 'all' || $type === 'peers') {
            $results['peers'] = Peer::with(['created_by'])
                ->where('name', 'like', "%{$query}%")
                ->orWhereHas('created_by', function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%");
                })
                ->withCount('users')
                ->limit(10)
                ->get();
        }

        if ($type === 'all' || $type === 'tournaments') {
            $results['tournaments'] = Tournament::where('name', 'like', "%{$query}%")
                ->withCount('users')
                ->limit(10)
                ->get();
        }

        if ($type === 'all' || $type === 'users') {
            $results['users'] = User::where('name', 'like', "%{$query}%")
                ->orWhere('email', 'like', "%{$query}%")
                ->limit(10)
                ->get();
        }

        if ($type === 'all' || $type === 'players') {
            $results['players'] = Player::where('name', 'like', "%{$query}%")
                ->orWhere('position', 'like', "%{$query}%")
                ->limit(10)
                ->get();
        }

        return Inertia::render('search/index', [
            'query' => $query,
            'type' => $type,
            'results' => $results
        ]);
    }

    public function api(Request $request)
    {
        $query = $request->get('q', '');
        $type = $request->get('type', 'all');

        if (empty($query)) {
            return response()->json([
                'peers' => [],
                'tournaments' => [],
                'users' => [],
                'players' => []
            ]);
        }

        $results = [];

        if ($type === 'all' || $type === 'peers') {
            $results['peers'] = Peer::with(['created_by'])
                ->where('name', 'like', "%{$query}%")
                ->orWhereHas('created_by', function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%");
                })
                ->withCount('users')
                ->limit(5)
                ->get();
        }

        if ($type === 'all' || $type === 'tournaments') {
            $results['tournaments'] = Tournament::where('name', 'like', "%{$query}%")
                ->withCount('users')
                ->limit(5)
                ->get();
        }

        if ($type === 'all' || $type === 'users') {
            $results['users'] = User::where('name', 'like', "%{$query}%")
                ->orWhere('email', 'like', "%{$query}%")
                ->limit(5)
                ->get();
        }

        if ($type === 'all' || $type === 'players') {
            $results['players'] = Player::where('name', 'like', "%{$query}%")
                ->orWhere('position', 'like', "%{$query}%")
                ->limit(5)
                ->get();
        }

        return response()->json($results);
    }
}
