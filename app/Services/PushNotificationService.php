<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\PushMessage;
use Illuminate\Support\Facades\Log;

class PushNotificationService
{
    public function send(User $user, array $payload)
    {
        try {
            $user->notify(new PushMessage(
                $payload['title'],
                $payload['body'],
                $payload['url'] ?? '/'
            ));
        } catch (\Exception $e) {
            Log::error("Failed to send push notification to user {$user->id}: " . $e->getMessage());
        }
    }
}
