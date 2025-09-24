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
        Route::get('/{player}', [PlayerController::class, 'show']);
        Route::post('/refetch', [PlayerController::class, 'refetch']);
    });

    Route::prefix('peers')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Peer\PeerController::class, 'index']);
    });

    Route::prefix('teams')->group(function () {
        Route::get('/', [TeamController::class, 'index']);
        Route::post('/refetch', [TeamController::class, 'refetch']);
        Route::patch('/{team}/status', [TeamController::class, 'updateStatus']);
        Route::get('/{team_id}/players', [TeamController::class, 'players']);
    });

    Route::prefix('match')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Match\MatchController::class, 'index']);
        Route::post('/create-from-fixture', [\App\Http\Controllers\Api\Match\MatchController::class, 'createFromFixture']);
        Route::post('/refetch-statistics', [\App\Http\Controllers\Api\Match\MatchController::class, 'refetchStatistics']);
        Route::get('/{playerMatch}/statistics', [\App\Http\Controllers\Api\Match\MatchController::class, 'getMatchStatistics']);
    });

    Route::prefix('countries')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Country\CountryController::class, 'index']);
        Route::get('/refetch', [\App\Http\Controllers\Api\Country\CountryController::class, 'refetch']);
    });

    Route::prefix('leagues')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Leagues\LeagueController::class, 'index']);
        Route::get('/seasons/{league}', [\App\Http\Controllers\Api\Leagues\LeagueController::class, 'getLeagueSeason']);
        Route::get('/season-rounde/{league}', [\App\Http\Controllers\Api\Leagues\LeagueController::class, 'getLeagueSeasonAndRound']);
        Route::post('/refetch', [\App\Http\Controllers\Api\Leagues\LeagueController::class, 'refetch']);
        Route::get('/{league}', [\App\Http\Controllers\Api\Leagues\LeagueController::class, 'show']);
    });

    Route::prefix('users')->group(function () {
        // Route::get('/', [\App\Http\Controllers\V1\User\UserController::class, 'index']);
    });

    Route::prefix('fixtures')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Fixture\FixtureController::class, 'index']);
        Route::post('/refetch', [\App\Http\Controllers\Api\Fixture\FixtureController::class, 'refetch']);
    });

      Route::prefix('tournament')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Tournament\TournamentController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Api\Tournament\TournamentController::class, 'store']);
    });
});
