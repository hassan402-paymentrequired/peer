<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    /**
     * Get user's notifications (paginated)
     */
    public function index(Request $request): JsonResponse
    {
        $user = authUser();
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 15);

        $notifications = Notification::where('user_id', $user->id)
            ->latest()
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json($notifications);
    }

    /**
     * Get unread notifications count
     */
    public function unreadCount(): JsonResponse
    {
        $user = authUser();

        $count = Notification::where('user_id', $user->id)
            ->unread()
            ->count();

        return response()->json(['unread_count' => $count]);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Request $request): JsonResponse
    {
        $request->validate([
            'notification_id' => 'required|exists:user_notifications,id'
        ]);

        $user = authUser();

        $notification = Notification::where('id', $request->notification_id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $notification->markAsRead();

        return response()->json([
            'message' => 'Notification marked as read',
            'notification' => $notification
        ]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(): JsonResponse
    {
        $user = authUser();

        $updatedCount = Notification::where('user_id', $user->id)
            ->unread()
            ->update(['read_at' => now()]);

        return response()->json([
            'message' => 'All notifications marked as read',
            'updated_count' => $updatedCount
        ]);
    }

    /**
     * Delete notification
     */
    public function destroy(Request $request): JsonResponse
    {
        $request->validate([
            'notification_id' => 'required|exists:user_notifications,id'
        ]);

        $user = authUser();

        $notification = Notification::where('id', $request->notification_id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $notification->delete();

        return response()->json([
            'message' => 'Notification deleted successfully'
        ]);
    }

    /**
     * Get recent notifications for real-time updates
     */
    public function recent(): JsonResponse
    {
        $user = authUser();

        $notifications = Notification::where('user_id', $user->id)
            ->latest()
            ->take(5)
            ->get();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => Notification::where('user_id', $user->id)->unread()->count()
        ]);
    }
}
