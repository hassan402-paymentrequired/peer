<?php

use App\Http\Controllers\Peer\PeerController;
use App\Http\Controllers\Tournament\TournamentController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {

    Route::prefix('peers')->group(function () {
        Route::get('/', [PeerController::class, 'index'])->name('dashboard');
        Route::get('/join/{peer:peer_id}', [PeerController::class, 'joinPeer'])->name('join.peer');
    });

    Route::prefix('tournament')->group(function () {
        Route::get('/', [TournamentController::class, 'index'])->name('tournament.index');
        Route::get('/join', [TournamentController::class, 'create'])->name('tournament.create');
        Route::post('/join', [TournamentController::class, 'store'])->name('tournament.store');
        Route::get('/{user}', [TournamentController::class, 'show'])->name('tournament.user.show');
    });
});



require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
