<?php

namespace App\Services;

use App\Models\User;
use App\Models\Notification;
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
        }
    }
}
