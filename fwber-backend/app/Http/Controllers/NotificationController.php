<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubscribePushRequest;
use App\Http\Requests\UnsubscribePushRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * List all notifications for the authenticated user.
     *
     * Returns the latest 50 notifications, ordered by newest first.
     * Each notification includes its read status via the `read_at` field.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $notifications = $user->notifications()
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(function ($notification) {
                $data = $notification->data;
                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'data' => $data,
                    'title' => $data['title'] ?? null,
                    'message' => $data['message'] ?? $data['body'] ?? null,
                    'read' => $notification->read_at !== null,
                    'read_at' => $notification->read_at,
                    'timestamp' => $notification->created_at->toISOString(),
                    'created_at' => $notification->created_at,
                ];
            });

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    /**
     * Mark a single notification as read.
     *
     * Uses Laravel's built-in markAsRead() which sets the `read_at` timestamp.
     * Returns 404 if the notification doesn't belong to the authenticated user.
     */
    public function markAsRead(string $id): JsonResponse
    {
        $user = Auth::user();
        $notification = $user->notifications()->where('id', $id)->first();

        if (!$notification) {
            return response()->json(['message' => 'Notification not found'], 404);
        }

        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read',
        ]);
    }

    /**
     * Mark all unread notifications as read for the authenticated user.
     *
     * Uses Laravel's built-in markAsRead() on the unreadNotifications collection.
     * This is an efficient bulk update (single SQL UPDATE).
     */
    public function markAllAsRead(): JsonResponse
    {
        $user = Auth::user();
        $user->unreadNotifications->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read',
        ]);
    }

    /**
     * Subscribe the user to push notifications.
     */
    public function subscribe(SubscribePushRequest $request): JsonResponse
    {
        $endpoint = $request->endpoint;
        $token = $request->keys['auth'];
        $key = $request->keys['p256dh'];

        $user = Auth::user();
        $user->updatePushSubscription($endpoint, $key, $token);

        return response()->json(['success' => true, 'message' => 'Push subscription updated.']);
    }

    /**
     * Unsubscribe the user from push notifications.
     */
    public function unsubscribe(UnsubscribePushRequest $request): JsonResponse
    {
        $user = Auth::user();
        $user->deletePushSubscription($request->endpoint);

        return response()->json(['success' => true, 'message' => 'Push subscription deleted.']);
    }
}
