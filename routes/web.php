<?php

use App\Http\Controllers\Peer\PeerController;
use App\Http\Controllers\Tournament\TournamentController;
use App\Http\Controllers\Wallet\WalletController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::redirect('/', '/login')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {

    Route::prefix('peers')->group(function () {
        Route::get('/', [PeerController::class, 'index'])->name('dashboard');
        Route::get('/create', [PeerController::class, 'create'])->name('peers.create');
        Route::post('/', [PeerController::class, 'store'])->name('peers.store');
        Route::get('/join/{peer:peer_id}', [PeerController::class, 'joinPeer'])->name('join.peer');
        Route::get('/my-contest', [PeerController::class, 'myGame'])->name('peers.contents');
         Route::post('/join/{peer}', [PeerController::class, 'storeJoinPeer'])->name('join.peer.store');
          Route::get('/{peer:peer_id}', [PeerController::class, 'show'])->name('peers.show');
    });

    Route::prefix('tournament')->group(function () {
        Route::get('/', [TournamentController::class, 'index'])->name('tournament.index');
        Route::get('/join', [TournamentController::class, 'create'])->name('tournament.create');
        Route::post('/join', [TournamentController::class, 'store'])->name('tournament.store');
        Route::get('/{user}', [TournamentController::class, 'show'])->name('tournament.user.show');
    });


    Route::prefix('wallet')->group(function () {
        Route::get('/', [WalletController::class, 'index'])->name('wallet.index');
        Route::get('/callback', [WalletController::class, 'paymentCallback'])->name('wallet.callback');
        Route::post('/fund', [WalletController::class, 'initializeFunding'])->name('wallet.fund');
        Route::post('/verify-payment', [WalletController::class, 'verifyPayment']);
        Route::get('/transactions', [WalletController::class, 'getTransactionHistory']);
        Route::get('/transactions/{transactionId}', [WalletController::class, 'getTransactionDetails']);
        Route::post('/bank-account-verify', [WalletController::class, 'verifyBankAccount'])->name('bank.account.verify');
        Route::post('/withdraw-funds', [WalletController::class, 'initiateWithdrawal'])->name('fund.withdraw');
        // Route::post('/transfer-verify', [WalletController::class, 'VerityTransfer'])->name('fund.transfer.verify');
    });
});

Route::prefix('webhooks')->group(function(){ 
    Route::post('/paystack/transfer-verify', [WalletController::class, 'processTransferWebhook']);
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
