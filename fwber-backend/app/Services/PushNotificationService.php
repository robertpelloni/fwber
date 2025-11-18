<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class PushNotificationService
{
    public function send(User $user, array $payload)
    {
        // Placeholder for sending a push notification.
        // In a real application, this would integrate with a push notification service like Firebase Cloud Messaging or Apple Push Notification Service.

        foreach ($user->deviceTokens as $deviceToken) {
            Log::info("Sending push notification to user {$user->id} on device {$deviceToken->token}", [
                'payload' => $payload,
            ]);
        }
    }
}
