<?php

use App\Http\Controllers\Api\Team\TeamController;
use App\Http\Controllers\Auth\AdminAuthController;
use App\Http\Controllers\Player\PlayerController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AdminAuthController::class, 'login'])
    ->name('admin.login');

Route::middleware(['auth:admin'])->group(function () {


    Route::prefix('players')->group(function () {
        Route::get('/', [PlayerController::class, 'index']);
        Route::get('/{player}', [PlayerController::class, 'show']);
        Route::patch('/star/{player}/update', [PlayerController::class, 'updatePlayerStar']);
        Route::patch('status/{player}/update', [PlayerController::class, 'updatePlayerStatus']);
        Route::get('/{player}', [PlayerController::class, 'show']);
        Route::post('/refetch', [PlayerController::class, 'refetch']);
    });

    Route::prefix('peers')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Peer\PeerController::class, 'index']);
    });

    Route::prefix('teams')->group(function () {
        Route::get('/', [TeamController::class, 'index']);
        Route::get('/active-teams', [TeamController::class, 'activeTeams']);
        Route::post('/refetch', [TeamController::class, 'refetch']);
        Route::patch('/{team}/status', [TeamController::class, 'updateStatus']);
        Route::get('/{team_id}/players', [TeamController::class, 'players']);
        Route::get('/active/{team_id}/players', [TeamController::class, 'activePlayer']);
        Route::get('/{team_id}/playing-players', [TeamController::class, 'playingPlayers']);
    });

    Route::prefix('match')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Match\MatchController::class, 'index']);
        Route::post('/create-from-fixture', [\App\Http\Controllers\Api\Match\MatchController::class, 'createFromFixture']);
        Route::post('/refetch-statistics', [\App\Http\Controllers\Api\Match\MatchController::class, 'refetchStatistics']);
        Route::get('/{playerMatch}/statistics', [\App\Http\Controllers\Api\Match\MatchController::class, 'getMatchStatistics']);

        // Player availability endpoints
        Route::get('/available-players/fixture', [\App\Http\Controllers\Api\Match\MatchController::class, 'getAvailablePlayersForFixture']);
        Route::get('/available-players/date', [\App\Http\Controllers\Api\Match\MatchController::class, 'getAvailablePlayersForDate']);
        Route::post('/check-availability', [\App\Http\Controllers\Api\Match\MatchController::class, 'checkPlayersAvailability']);
        Route::get('/player-status', [\App\Http\Controllers\Api\Match\MatchController::class, 'getPlayerAvailabilityStatus']);
        Route::get('/availability-summary', [\App\Http\Controllers\Api\Match\MatchController::class, 'getAvailabilitySummary']);
        Route::get('/ongoing-matches', [\App\Http\Controllers\Api\Match\MatchController::class, 'getPlayersInOngoingMatches']);

        // Lineup management endpoints
        Route::post('/fetch-lineup', [\App\Http\Controllers\Api\Match\MatchController::class, 'fetchFixtureLineup']);
        Route::get('/lineup', [\App\Http\Controllers\Api\Match\MatchController::class, 'getFixtureLineup']);
    });

    Route::prefix('countries')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Country\CountryController::class, 'index']);
        Route::patch('/{country}/status', [\App\Http\Controllers\Api\Country\CountryController::class, 'update']);
        Route::get('/active-countries', [\App\Http\Controllers\Api\Country\CountryController::class, 'activeCountry']);
        Route::get('/refetch', [\App\Http\Controllers\Api\Country\CountryController::class, 'refetch']);
        Route::get('/{country}', [\App\Http\Controllers\Api\Country\CountryController::class, 'show']);
    });

    Route::prefix('leagues')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Leagues\LeagueController::class, 'index']);
        Route::get('/seasons/{league}', [\App\Http\Controllers\Api\Leagues\LeagueController::class, 'getLeagueSeason']);
        Route::get('/season-rounde/{league}', [\App\Http\Controllers\Api\Leagues\LeagueController::class, 'getLeagueSeasonAndRound']);
        Route::post('/refetch', [\App\Http\Controllers\Api\Leagues\LeagueController::class, 'refetch']);
        Route::patch('/{league}/status', [\App\Http\Controllers\Api\Leagues\LeagueController::class, 'update']);
        Route::get('/active-leagues', [\App\Http\Controllers\Api\Leagues\LeagueController::class, 'activeCountry']);
        Route::get('/{league}', [\App\Http\Controllers\Api\Leagues\LeagueController::class, 'show']);
    });

    Route::prefix('users')->group(function () {
        // Route::get('/', [\App\Http\Controllers\V1\User\UserController::class, 'index']);
    });

      Route::prefix('withdraw')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\User\UserController::class, 'index']);
        Route::patch('/{withdrawRequest}/status', [\App\Http\Controllers\Api\User\UserController::class, 'update']);
    });

    Route::prefix('fixtures')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Fixture\FixtureController::class, 'index']);
        Route::get('/active-fixtures', [\App\Http\Controllers\Api\Fixture\FixtureController::class, 'activeFixtures']);
        Route::post('/refetch', [\App\Http\Controllers\Api\Fixture\FixtureController::class, 'refetch']);
    });

    Route::prefix('tournament')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Tournament\TournamentController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Api\Tournament\TournamentController::class, 'store']);
    });

    Route::prefix('scoring')->group(function () {
        Route::post('/fetch-live-statistics', [\App\Http\Controllers\Api\Admin\ScoringController::class, 'fetchLiveStatistics']);
        Route::post('/calculate-tournament-scores', [\App\Http\Controllers\Api\Admin\ScoringController::class, 'calculateTournamentScores']);
        Route::post('/calculate-peer-scores', [\App\Http\Controllers\Api\Admin\ScoringController::class, 'calculatePeerScores']);
        Route::get('/competition-status', [\App\Http\Controllers\Api\Admin\ScoringController::class, 'getCompetitionStatus']);
    });
});
