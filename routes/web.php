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
        Route::get('/verify-transfer/{transferId}', [WalletController::class, 'verifyTransfer'])->name('wallet.verify-transfer');
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

    Route::prefix('sms')->group(function () {
        Route::post('/test', [\App\Http\Controllers\SmsController::class, 'sendTestSms'])->name('sms.test');
        Route::get('/balance', [\App\Http\Controllers\SmsController::class, 'getBalance'])->name('sms.balance');
        Route::post('/update-phone', [\App\Http\Controllers\SmsController::class, 'updatePhone'])->name('sms.update-phone');
        Route::post('/send-otp', [\App\Http\Controllers\SmsController::class, 'sendOtp'])->name('sms.send-otp');
        Route::post('/verify-otp', [\App\Http\Controllers\SmsController::class, 'verifyOtp'])->name('sms.verify-otp');
        Route::get('/sender-ids', [\App\Http\Controllers\SmsController::class, 'getSenderIds'])->name('sms.sender-ids');
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

// Test routes for webhook verification (remove in production)
Route::prefix('test-webhook')->middleware(['auth'])->group(function () {
    Route::get('/simulate-transfer', function () {
        return view('test-webhook');
    });

    Route::post('/simulate-flutterwave', function (Illuminate\Http\Request $request) {
        // Simulate a Flutterwave transfer webhook
        $testPayload = [
            'event' => 'transfer.completed',
            'data' => [
                'id' => 'test_transfer_' . time(),
                'reference' => $request->get('reference', 'STA_test_reference'),
                'status' => $request->get('status', 'SUCCESSFUL'),
                'amount' => $request->get('amount', 1000),
                'fee' => 50,
                'currency' => 'NGN',
                'bank_name' => 'Test Bank',
                'account_number' => '1234567890',
                'created_at' => now()->toISOString(),
                'complete_message' => $request->get('status') === 'FAILED' ? 'Transfer failed' : 'Transfer successful',
            ]
        ];

        // Make internal request to webhook endpoint
        $response = app()->handle(
            Illuminate\Http\Request::create(
                '/webhooks/flutterwave/webhook',
                'POST',
                $testPayload,
                [],
                [],
                ['HTTP_verif-hash' => env('FLW_SECRET_HASH')]
            )
        );

        return response()->json([
            'test_payload' => $testPayload,
            'webhook_response' => [
                'status_code' => $response->getStatusCode(),
                'content' => json_decode($response->getContent(), true)
            ]
        ]);
    });
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
