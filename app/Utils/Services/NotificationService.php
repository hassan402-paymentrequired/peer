<?php

namespace App\Utils\Services;

use Illuminate\Http\Request;

class NotificationService
{
    public static function send($user, $notification)
    {
        $user->notify($notification);
    }

    public function storeSubscription(Request $request)
    {
        $user = $request->user();
        $subscription = $request->all();
        if (!isset($subscription['endpoint'])) {
            return response()->json(['error' => 'Invalid subscription data'], 400);
        }

        $user->updatePushSubscription(
            $subscription['endpoint'],
            $subscription['keys']['p256dh'] ?? null,
            $subscription['keys']['auth'] ?? null,
            $subscription['contentEncoding'] ?? 'aesgcm'
        );

        return response()->json(['success' => true]);
    }
}
