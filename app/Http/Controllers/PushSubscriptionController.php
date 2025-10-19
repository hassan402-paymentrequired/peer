<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class PushSubscriptionController extends Controller
{
    /**
     * Save push subscription for the authenticated user
     */
    public function saveSubscription(Request $request): JsonResponse
    {
        try {
            $user = authUser();
            $subscription = $request->getContent();

            // Validate that we have a valid subscription
            $subscriptionData = json_decode($subscription, true);

            if (!$subscriptionData || !isset($subscriptionData['endpoint'])) {
                return response()->json(['error' => 'Invalid subscription data'], 400);
            }

            // Update user's push subscription
            $user->updatePushSubscription($subscriptionData['endpoint'], $subscriptionData['keys']['p256dh'], $subscriptionData['keys']['auth']);

            Log::info("Push subscription saved for user {$user->id}");

            return response()->json(['message' => 'Subscription saved successfully']);
        } catch (\Exception $e) {
            Log::error("Failed to save push subscription: " . $e->getMessage());
            return response()->json(['error' => 'Failed to save subscription'], 500);
        }
    }
}
