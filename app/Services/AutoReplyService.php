<?php

namespace App\Services;

use App\Models\User;
use App\Models\Message;
use Carbon\Carbon;

class AutoReplyService implements AutoReplyServiceInterface
{
    public function shouldReply(int $userId, string $incomingMessage): bool
    {
        $user = User::find($userId);
        if (!$user) {
            return false;
        }

        // Simple logic: Reply if user hasn't been seen in 15 minutes
        // and the message is a greeting (very basic heuristic)
        $isOffline = $user->last_seen_at && $user->last_seen_at->diffInMinutes(Carbon::now()) > 15;
        
        $isGreeting = preg_match('/^(hi|hello|hey|sup|yo)\b/i', trim($incomingMessage));

        return $isOffline && $isGreeting;
    }

    public function generateReply(int $userId): string
    {
        // In a real app, this might vary based on user personality or settings
        return "Hey! I'm currently offline but I'll get back to you soon. Send a pic?";
    }

    public function sendReply(int $senderId, int $recipientId, string $content): void
    {
        Message::create([
            'sender_id' => $senderId,
            'receiver_id' => $recipientId,
            'content' => $content,
            'message_type' => 'text',
            'sent_at' => Carbon::now(),
            'is_read' => false,
        ]);
    }
}
