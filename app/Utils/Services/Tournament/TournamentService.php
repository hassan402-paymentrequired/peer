<?php

namespace App\Utils\Services\Tournament;

use App\Exceptions\ClientErrorException;
use App\Models\Tournament;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TournamentService
{
    public function create($request, $tournament = null, $guard = WEB)
    {
        if ($tournament->users()->where('user_id', Auth::guard($guard)->id())->exists()) {
            return false;
        }

        // Create peer_user record
        $contestUser = \App\Models\TournamentUser::create([
            'tournament_id' => $tournament->id,
            'user_id' => Auth::guard($guard)->id(),
            'total_points' => 0,
            'is_winner' => false
        ]);

        // Create peer_user_squad records for each squad member
        foreach ($request->peers as $value) {
            \App\Models\TournamentUserSquard::create([
                'tournament_user_id' => $contestUser->id,
                'star_rating' => $value['star'] ?? 1,
                'main_player_id' => $value['main'],
                'sub_player_id' => $value['sub'],
                'main_player_match_id' => $value['main_player_match_id'],
                'sub_player_match_id' => $value['sub_player_match_id'],
            ]);
        }
        return true;
    }

    public function createNewTournament(array $payload)
    {
        try {
            DB::beginTransaction();

            $tourn = Tournament::active()->first();
            $tourn->update(['is_active' => false]);

            Tournament::create($payload);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::info("TournamentService error:", [
                'error' => $e->getMessage()
            ]);
            throw new ClientErrorException("Error occur while creating tournament");
        }
    }

    public function getAllsTournament()
    {
        try {
            return Tournament::query()->paginate(20);
        } catch (\Throwable $e) {
            Log::info("TournamentService error:", [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
