<?php

namespace App\Services;

use App\Mail\NewMatchNotification;
use App\Mail\UnreadMessagesNotification;
use App\Models\User;
use App\Models\Message;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class EmailNotificationService
{
    public function __construct(
        private FeatureFlagService $flags
    ) {}

    /**
     * Send new match notification email
     */
    public function sendNewMatchNotification(User $recipient, User $match): void
    {
        if (!$this->flags->isEnabled('email_notifications')) {
            return;
        }

        $matchProfile = $match->profile;
        if (!$matchProfile) {
            return;
        }

        Mail::to($recipient->email)->send(new NewMatchNotification(
            matchName: $matchProfile->display_name ?? $match->name,
            matchBio: $matchProfile->bio ?? 'No bio yet',
            matchAvatarUrl: $matchProfile->avatar_url,
            compatibilityScore: 85, // Could calculate dynamically
            appUrl: config('app.url')
        ));
    }

    /**
     * Send unread messages notification with debounce
     * Only sends if user hasn't been notified in the last 6 hours
     */
    public function sendUnreadMessagesNotification(User $recipient): void
    {
        if (!$this->flags->isEnabled('email_notifications')) {
            return;
        }

        $cacheKey = "email_unread_notification_sent:{$recipient->id}";
        
        // Debounce: don't send if we sent one recently
        if (Cache::has($cacheKey)) {
            return;
        }

        // Get unread messages grouped by sender
        $unreadMessages = Message::where('receiver_id', $recipient->id)
            ->whereNull('read_at')
            ->with('sender')
            ->orderBy('sent_at', 'desc')
            ->get();

        if ($unreadMessages->isEmpty()) {
            return;
        }

        // Group by sender and take preview
        $senders = $unreadMessages->groupBy('sender_id')
            ->map(function ($messages) {
                $sender = $messages->first()->sender;
                $latestMessage = $messages->first();
                
                return [
                    'name' => $sender->name,
                    'message_preview' => \Illuminate\Support\Str::limit($latestMessage->content, 60),
                ];
            })
            ->take(3) // Max 3 senders in email
            ->values()
            ->toArray();

        Mail::to($recipient->email)->send(new UnreadMessagesNotification(
            unreadCount: $unreadMessages->count(),
            senders: $senders,
            appUrl: config('app.url')
        ));

        // Set debounce cache for 6 hours
        Cache::put($cacheKey, true, now()->addHours(6));
    }

    /**
     * Send batch unread notifications to all users with unread messages
     * Call this from a scheduled job/cron
     */
    public function sendBatchUnreadNotifications(): int
    {
        if (!$this->flags->isEnabled('email_notifications')) {
            return 0;
        }

        $recipientsWithUnread = Message::whereNull('read_at')
            ->distinct('receiver_id')
            ->pluck('receiver_id');

        $sent = 0;
        foreach ($recipientsWithUnread as $userId) {
            $user = User::find($userId);
            if ($user) {
                $this->sendUnreadMessagesNotification($user);
                $sent++;
            }
        }

        return $sent;
    }
}
