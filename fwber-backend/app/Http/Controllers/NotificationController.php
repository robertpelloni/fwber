<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubscribePushRequest;
use App\Http\Requests\UnsubscribePushRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class NotificationController extends Controller
{
    private function normalizeJsonValue(mixed $value): mixed
    {
        if (\is_array($value)) {
            $normalized = [];

            foreach ($value as $key => $item) {
                $normalized[$key] = $this->normalizeJsonValue($item);
            }

            return $normalized;
        }

        if (\is_string($value) && ! mb_check_encoding($value, 'UTF-8')) {
            return mb_scrub($value, 'UTF-8');
        }

        return $value;
    }

    private function decodeNotificationData(mixed $rawData, string $notificationId): array
    {
        if (\is_array($rawData)) {
            return $this->normalizeJsonValue($rawData);
        }

        if (! \is_string($rawData) || $rawData === '') {
            return [];
        }

        try {
            $decoded = json_decode($rawData, true, 512, JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);
        } catch (\JsonException $exception) {
            Log::warning('Skipping malformed notification payload', [
                'notification_id' => $notificationId,
                'error' => $exception->getMessage(),
            ]);

            return [];
        }

        return \is_array($decoded) ? $this->normalizeJsonValue($decoded) : [];
    }

    private function normalizeTimestamp(mixed $timestamp): ?string
    {
        if ($timestamp === null) {
            return null;
        }

        if ($timestamp instanceof Carbon) {
            return $timestamp->toISOString();
        }

        return Carbon::parse($timestamp)->toISOString();
    }

    private function transformNotification(object $notification, array $data): array
    {
        $title = $data['title'] ?? $data['subject'] ?? 'Notification';
        $message = $data['message'] ?? $data['body'] ?? $data['content'] ?? '';

        return [
            'id' => $notification->id,
            'type' => $data['type'] ?? class_basename($notification->type),
            'data' => $this->normalizeJsonValue($data),
            'title' => $this->normalizeJsonValue($title),
            'body' => $this->normalizeJsonValue($message),
            'message' => $this->normalizeJsonValue($message),
            'read' => $notification->read_at !== null,
            'read_at' => $this->normalizeTimestamp($notification->read_at),
            'timestamp' => $this->normalizeTimestamp($notification->created_at),
            'created_at' => $this->normalizeTimestamp($notification->created_at),
        ];
    }

    private function usesLegacyNotificationsSchema(): bool
    {
        return Schema::hasColumn('notifications', 'user_id')
            && ! Schema::hasColumn('notifications', 'notifiable_type')
            && ! Schema::hasColumn('notifications', 'notifiable_id');
    }

    private function legacyNotificationData(object $notification): array
    {
        return [
            'title' => $notification->title ?? 'Notification',
            'body' => $notification->body ?? '',
            'message' => $notification->body ?? '',
            'type' => 'system',
        ];
    }

    /**
     * List all notifications for the authenticated user.
     *
     * Returns the latest 50 notifications, ordered by newest first.
     * Each notification includes its read status via the `read_at` field.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $notificationsQuery = DB::table('notifications')->orderBy('created_at', 'desc')->limit(50);

        if ($this->usesLegacyNotificationsSchema()) {
            $notifications = $notificationsQuery
                ->where('user_id', $user->getKey())
                ->get()
                ->map(fn (object $notification) => $this->transformNotification(
                    $notification,
                    $this->legacyNotificationData($notification)
                ));
        } else {
            $notifications = $notificationsQuery
                ->where('notifiable_type', $user->getMorphClass())
                ->where('notifiable_id', $user->getKey())
                ->get()
                ->map(function (object $notification) {
                    $data = $this->decodeNotificationData($notification->data ?? null, (string) $notification->id);

                    return $this->transformNotification($notification, $data);
                });
        }

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $this->usesLegacyNotificationsSchema()
                ? DB::table('notifications')
                    ->where('user_id', $user->getKey())
                    ->whereNull('read_at')
                    ->count()
                : $user->unreadNotifications()->count(),
        ], 200, [], JSON_INVALID_UTF8_SUBSTITUTE);
    }

    public function count(): JsonResponse
    {
        $user = Auth::user();

        return response()->json([
            'unread_count' => $this->usesLegacyNotificationsSchema()
                ? DB::table('notifications')
                    ->where('user_id', $user->getKey())
                    ->whereNull('read_at')
                    ->count()
                : $user->unreadNotifications()->count(),
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

        if (! $notification) {
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
