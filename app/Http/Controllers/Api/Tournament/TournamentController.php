<?php

namespace App\Http\Controllers\Api\Tournament;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tournament\TournamentRequest;
use App\Utils\Services\Tournament\TournamentService;

class TournamentController extends Controller
{

    private TournamentService $tournamentService;

    public function __construct(TournamentService $tournamentService)
    {
        $this->tournamentService = $tournamentService;
    }


    public function index()
    {
       $tournaments =  $this->tournamentService->getAllsTournament();
        return $this->respondWithCustomData([
            'tournaments' => $tournaments,
            'message' => 'Tournaments retrieved successfully'
        ]);
    }


    public function store(TournamentRequest $request)
    {
        $this->tournamentService->createNewTournament($request->validated());
        return $this->respondWithCustomData([
            'message' => 'Tournament Created successfully'
        ]);
    }
}
