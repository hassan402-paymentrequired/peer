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

        if (empty($query)) {
            return Inertia::render('search/index', [
                'query' => $query,
                'results' => [
                    'peers' => [],
                    'tournaments' => [],
                    'users' => [],
                    'players' => []
                ]
            ]);
        }

        $results = [];

         $results['peers'] = Peer::with(['created_by'])
                ->where('name', 'like', "%{$query}%")
                ->orWhereHas('created_by', function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%");
                })
                ->withCount('users')
                ->limit(10)
                ->get();

        return Inertia::render('search/index', [
            'query' => $query,
            'results' => $results
        ]);
    }

    public function api(Request $request)
    {
        $query = $request->get('q', '');

        if (empty($query)) {
            return response()->json([
                'peers' => []
            ]);
        }

        $results = [];

 $results['peers'] = Peer::with(['created_by'])
                ->where('name', 'like', "%{$query}%")
                ->orWhereHas('created_by', function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%");
                })
                ->withCount('users')
                ->limit(5)
                ->get();

        return response()->json($results);
    }
}
