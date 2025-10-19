<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TestNotificationController extends Controller
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Send a test notification to the authenticated user
     */
    public function sendTestNotification(Request $request): JsonResponse
    {
        $user = authUser();

        $this->notificationService->createNotification(
            $user,
            '🧪 Test Notification',
            'This is a test notification sent from the API to verify the real-time notification system is working correctly.',
            'test_notification',
            [
                'test_data' => 'This is test data',
                'timestamp' => now()->toISOString(),
            ]
        );

        return response()->json([
            'message' => 'Test notification sent successfully',
            'user_id' => $user->id,
        ]);
    }

    /**
     * Send a test tournament completion notification
     */
    public function sendTestTournamentNotification(Request $request): JsonResponse
    {
        $user = authUser();

        $this->notificationService->createNotification(
            $user,
            '🏆 Test Tournament Completed',
            'Your test tournament has completed! You finished in 2nd place with 85 points.',
            'tournament_completed',
            [
                'tournament_id' => 999,
                'tournament_name' => 'Test Daily Tournament',
                'final_rank' => 2,
                'total_points' => 85,
                'is_winner' => false,
                'prize_amount' => 0,
            ]
        );

        return response()->json([
            'message' => 'Test tournament notification sent successfully',
        ]);
    }

    /**
     * Send a test prize won notification
     */
    public function sendTestPrizeNotification(Request $request): JsonResponse
    {
        $user = authUser();

        $this->notificationService->notifyPrizeWon(
            $user,
            2500.00,
            'tournament',
            'Test Championship Tournament',
            [
                'tournament_id' => 888,
                'final_rank' => 1,
            ]
        );

        return response()->json([
            'message' => 'Test prize notification sent successfully',
        ]);
    }

    /**
     * Send a test WebPush notification directly
     */
    public function sendTestWebPush(Request $request): JsonResponse
    {
        try {
            $user = authUser();

            Log::info("Sending test WebPush notification to user {$user->id}");

            // Check if user has push subscriptions
            $subscriptions = $user->pushSubscriptions()->count();
            Log::info("User has {$subscriptions} push subscriptions");

            // Send WebPush notification directly
            $user->notify(new \App\Notifications\TournamentCompletedNotification(
                'Test Tournament',
                true,
                85,
                1500.00
            ));

            return response()->json([
                'message' => 'Test WebPush notification sent successfully',
                'user_id' => $user->id,
                'subscriptions_count' => $subscriptions,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send test WebPush notification: " . $e->getMessage());
            return response()->json([
                'error' => 'Failed to send notification: ' . $e->getMessage(),
            ], 500);
        }
    }
}
