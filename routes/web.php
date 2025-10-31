<?php

use App\Http\Controllers\Peer\PeerController;
use App\Http\Controllers\Tournament\TournamentController;
use App\Http\Controllers\Wallet\WalletController;
use App\Notifications\TestNotification;
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
        Route::get('/completed-game', [PeerController::class, 'completedContest'])->name('peers.completed');
        Route::post('/join/{peer}', [PeerController::class, 'storeJoinPeer'])->name('join.peer.store');
        Route::get('/{peer:peer_id}', [PeerController::class, 'show'])->name('peers.show');

        // API endpoints for infinite scrollb
        Route::get('/api/ongoing', [PeerController::class, 'getOngoingPeers'])->name('peers.api.ongoing');
        Route::get('/api/completed', [PeerController::class, 'getCompletedPeers'])->name('peers.api.completed');
    });

    Route::prefix('tournament')->group(function () {
        Route::get('/', [TournamentController::class, 'index'])->name('tournament.index');
        Route::get('/join', [TournamentController::class, 'create'])->name('tournament.create');
        Route::post('/join', [TournamentController::class, 'store'])->name('tournament.store');
        Route::get('/leaderboard', [TournamentController::class, 'leaderboard'])->name('tournament.leaderboard');
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
    });

    Route::prefix('notifications')->group(function () {
        Route::get('/', [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
        Route::get('/unread-count', [\App\Http\Controllers\NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
        Route::post('/mark-read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.mark-read');
        Route::post('/mark-all-read', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
        Route::delete('/delete', [\App\Http\Controllers\NotificationController::class, 'destroy'])->name('notifications.delete');
        Route::get('/recent', [\App\Http\Controllers\NotificationController::class, 'recent'])->name('notifications.recent');
    });

    Route::prefix('search')->group(function () {
        Route::get('/', [\App\Http\Controllers\SearchController::class, 'index'])->name('search.index');
        Route::get('/api', [\App\Http\Controllers\SearchController::class, 'api'])->name('search.api');
    });

    // Push subscription route
    Route::post('/save-subscription', [\App\Http\Controllers\PushSubscriptionController::class, 'saveSubscription'])->name('push.save-subscription');

    // Test notification page
    Route::get('/test-notifications', function () {
        return inertia('TestNotifications');
    })->name('test-notifications');

    // Test notification routes (remove in production)
    Route::prefix('api/test')->group(function () {
        Route::post('/notification', [\App\Http\Controllers\Api\TestNotificationController::class, 'sendTestNotification'])->name('test.notification');
        Route::post('/tournament-notification', [\App\Http\Controllers\Api\TestNotificationController::class, 'sendTestTournamentNotification'])->name('test.tournament-notification');
        Route::post('/prize-notification', [\App\Http\Controllers\Api\TestNotificationController::class, 'sendTestPrizeNotification'])->name('test.prize-notification');
        Route::post('/webpush-notification', [\App\Http\Controllers\Api\TestNotificationController::class, 'sendTestWebPush'])->name('test.webpush-notification');
    });
});

Route::prefix('webhooks')->group(function () {
    Route::post('/flutterwave/webhook', [WalletController::class, 'processTransferWebhook']);
});

Route::post('/save-subscription', [\App\Utils\Services\NotificationService::class, 'storeSubscription'])->name('save.subscription');

Route::get('/test', function () {
    $user = \App\Models\User::find(1);

    $user->notify(new TestNotification());
    return 'hello';
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
