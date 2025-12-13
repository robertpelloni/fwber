<?php

namespace App\Notifications\Traits;

use NotificationChannels\WebPush\WebPushChannel;

trait ChecksNotificationPreferences
{
    /**
     * Get the notification's delivery channels based on user preferences.
     *
     * @param object $notifiable
     * @param string $type
     * @param array $defaultChannels
     * @return array
     */
    protected function getChannels(object $notifiable, string $type, array $defaultChannels = ['mail', 'database', WebPushChannel::class]): array
    {
        // If notifiable is not a User (e.g. AnonymousNotifiable), return defaults
        if (!method_exists($notifiable, 'notificationPreferences')) {
            return $defaultChannels;
        }

        $preference = $notifiable->notificationPreferences()->where('type', $type)->first();

        // If no preference set, return defaults (assuming opt-out model)
        if (!$preference) {
            return $defaultChannels;
        }

        $channels = [];

        if ($preference->mail) {
            $channels[] = 'mail';
        }

        if ($preference->database) {
            $channels[] = 'database';
        }

        if ($preference->push) {
            $channels[] = WebPushChannel::class;
        }

        return $channels;
    }
}
