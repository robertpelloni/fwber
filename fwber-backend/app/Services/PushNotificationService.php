<?php

namespace App\Services;

use App\Models\User;
use App\Models\Notification;
use App\Notifications\PushMessage;
use Illuminate\Support\Facades\Log;

class PushNotificationService
{
    public function send(User $user, array $payload)
    {
        // Create a new notification record in the database.
        Notification::create([
            'user_id' => $user->id,
            'title' => $payload['title'],
            'body' => $payload['body'],
        ]);

        // Placeholder for sending a push notification.
        // In a real application, this would integrate with a push notification service like Firebase Cloud Messaging or Apple Push Notification Service.
        foreach ($user->deviceTokens as $deviceToken) {
            Log::info("Sending push notification to user {$user->id} on device {$deviceToken->token}", [
                'payload' => $payload,
            ]);
    public function send(User $user, array $payload, string $type = 'marketing')
    {
        try {
            $user->notify(new PushMessage(
                $payload['title'],
                $payload['body'],
                $payload['url'] ?? '/',
                $type
            ));
        } catch (\Exception $e) {
            Log::error("Failed to send push notification to user {$user->id}: " . $e->getMessage());
        }
    }
}
