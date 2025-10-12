<?php

namespace App\Services;

use App\Models\User;
use App\Models\Message;
use App\Models\Match;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class RealTimeCommunicationService
{
    private $redis;
    private $presencePrefix = 'user:presence:';
    private $typingPrefix = 'user:typing:';
    private $messagePrefix = 'message:';

    public function __construct()
    {
        $this->redis = Redis::connection();
    }

    public function setUserOnline(int $userId): void
    {
        $this->redis->setex(
            $this->presencePrefix . $userId,
            300, // 5 minutes TTL
            json_encode([
                'user_id' => $userId,
                'status' => 'online',
                'last_seen' => now()->toISOString(),
            ])
        );

        // Publish presence update
        $this->publishPresenceUpdate($userId, 'online');
    }

    public function setUserOffline(int $userId): void
    {
        $this->redis->del($this->presencePrefix . $userId);
        $this->publishPresenceUpdate($userId, 'offline');
    }

    public function getUserPresence(int $userId): ?array
    {
        $presence = $this->redis->get($this->presencePrefix . $userId);
        return $presence ? json_decode($presence, true) : null;
    }

    public function setTypingStatus(int $userId, int $targetUserId, bool $isTyping): void
    {
        if ($isTyping) {
            $this->redis->setex(
                $this->typingPrefix . $userId . ':' . $targetUserId,
                10, // 10 seconds TTL
                json_encode([
                    'user_id' => $userId,
                    'target_user_id' => $targetUserId,
                    'is_typing' => true,
                    'timestamp' => now()->toISOString(),
                ])
            );
        } else {
            $this->redis->del($this->typingPrefix . $userId . ':' . $targetUserId);
        }

        $this->publishTypingUpdate($userId, $targetUserId, $isTyping);
    }

    public function getTypingStatus(int $userId, int $targetUserId): ?array
    {
        $typing = $this->redis->get($this->typingPrefix . $targetUserId . ':' . $userId);
        return $typing ? json_decode($typing, true) : null;
    }

    public function sendMessage(int $senderId, int $receiverId, string $content, ?string $messageType = 'text'): array
    {
        // Validate that users can communicate
        if (!$this->canUsersCommunicate($senderId, $receiverId)) {
            throw new \Exception('Users cannot communicate');
        }

        // Create message record
        $message = Message::create([
            'sender_id' => $senderId,
            'receiver_id' => $receiverId,
            'content' => $content,
            'message_type' => $messageType,
            'is_read' => false,
            'sent_at' => now(),
        ]);

        // Publish message to real-time channel
        $this->publishMessage($message);

        // Update match last message timestamp
        $this->updateMatchLastMessage($senderId, $receiverId);

        // Send push notification if receiver is offline
        $this->sendPushNotification($receiverId, $message);

        return [
            'id' => $message->id,
            'sender_id' => $message->sender_id,
            'receiver_id' => $message->receiver_id,
            'content' => $message->content,
            'message_type' => $message->message_type,
            'sent_at' => $message->sent_at->toISOString(),
            'is_read' => $message->is_read,
        ];
    }

    public function markMessageAsRead(int $messageId, int $userId): void
    {
        $message = Message::where('id', $messageId)
            ->where('receiver_id', $userId)
            ->first();

        if ($message && !$message->is_read) {
            $message->update(['is_read' => true, 'read_at' => now()]);
            $this->publishMessageRead($message);
        }
    }

    public function getConversation(int $userId1, int $userId2, int $limit = 50): array
    {
        $messages = Message::where(function ($query) use ($userId1, $userId2) {
            $query->where('sender_id', $userId1)->where('receiver_id', $userId2)
                  ->orWhere('sender_id', $userId2)->where('receiver_id', $userId1);
        })
        ->orderBy('sent_at', 'desc')
        ->limit($limit)
        ->get();

        return $messages->map(function ($message) {
            return [
                'id' => $message->id,
                'sender_id' => $message->sender_id,
                'receiver_id' => $message->receiver_id,
                'content' => $message->content,
                'message_type' => $message->message_type,
                'sent_at' => $message->sent_at->toISOString(),
                'is_read' => $message->is_read,
                'read_at' => $message->read_at?->toISOString(),
            ];
        })->toArray();
    }

    public function getUnreadMessageCount(int $userId): int
    {
        return Message::where('receiver_id', $userId)
            ->where('is_read', false)
            ->count();
    }

    public function getOnlineFriends(int $userId): array
    {
        // Get user's matches
        $matches = Match::where('is_active', true)
            ->where(function ($query) use ($userId) {
                $query->where('user1_id', $userId)->orWhere('user2_id', $userId);
            })
            ->get();

        $friendIds = $matches->map(function ($match) use ($userId) {
            return $match->user1_id === $userId ? $match->user2_id : $match->user1_id;
        })->toArray();

        $onlineFriends = [];
        foreach ($friendIds as $friendId) {
            $presence = $this->getUserPresence($friendId);
            if ($presence && $presence['status'] === 'online') {
                $onlineFriends[] = [
                    'user_id' => $friendId,
                    'last_seen' => $presence['last_seen'],
                ];
            }
        }

        return $onlineFriends;
    }

    private function canUsersCommunicate(int $userId1, int $userId2): bool
    {
        return Match::where('is_active', true)
            ->where(function ($query) use ($userId1, $userId2) {
                $query->where('user1_id', $userId1)->where('user2_id', $userId2)
                      ->orWhere('user1_id', $userId2)->where('user2_id', $userId1);
            })
            ->exists();
    }

    private function publishPresenceUpdate(int $userId, string $status): void
    {
        $this->redis->publish('presence_updates', json_encode([
            'user_id' => $userId,
            'status' => $status,
            'timestamp' => now()->toISOString(),
        ]));
    }

    private function publishTypingUpdate(int $userId, int $targetUserId, bool $isTyping): void
    {
        $this->redis->publish('typing_updates', json_encode([
            'user_id' => $userId,
            'target_user_id' => $targetUserId,
            'is_typing' => $isTyping,
            'timestamp' => now()->toISOString(),
        ]));
    }

    private function publishMessage(Message $message): void
    {
        $this->redis->publish('message_updates', json_encode([
            'id' => $message->id,
            'sender_id' => $message->sender_id,
            'receiver_id' => $message->receiver_id,
            'content' => $message->content,
            'message_type' => $message->message_type,
            'sent_at' => $message->sent_at->toISOString(),
            'is_read' => $message->is_read,
        ]));
    }

    private function publishMessageRead(Message $message): void
    {
        $this->redis->publish('message_read_updates', json_encode([
            'message_id' => $message->id,
            'receiver_id' => $message->receiver_id,
            'read_at' => $message->read_at->toISOString(),
        ]));
    }

    private function updateMatchLastMessage(int $userId1, int $userId2): void
    {
        Match::where('is_active', true)
            ->where(function ($query) use ($userId1, $userId2) {
                $query->where('user1_id', $userId1)->where('user2_id', $userId2)
                      ->orWhere('user1_id', $userId2)->where('user2_id', $userId1);
            })
            ->update(['last_message_at' => now()]);
    }

    private function sendPushNotification(int $receiverId, Message $message): void
    {
        $presence = $this->getUserPresence($receiverId);
        
        // Only send push notification if user is offline
        if (!$presence || $presence['status'] !== 'online') {
            // In a real implementation, this would integrate with FCM, APNS, or similar
            Log::info("Push notification for user {$receiverId}: New message from {$message->sender_id}");
        }
    }

    public function initiateVideoCall(int $userId, int $targetUserId): array
    {
        if (!$this->canUsersCommunicate($userId, $targetUserId)) {
            throw new \Exception('Users cannot initiate video call');
        }

        $callId = uniqid('call_');
        
        // Store call information
        $this->redis->setex(
            "call:{$callId}",
            3600, // 1 hour TTL
            json_encode([
                'call_id' => $callId,
                'initiator_id' => $userId,
                'target_id' => $targetUserId,
                'status' => 'initiated',
                'created_at' => now()->toISOString(),
            ])
        );

        // Publish call initiation
        $this->redis->publish('video_call_updates', json_encode([
            'call_id' => $callId,
            'initiator_id' => $userId,
            'target_id' => $targetUserId,
            'action' => 'initiate',
            'timestamp' => now()->toISOString(),
        ]));

        return [
            'call_id' => $callId,
            'initiator_id' => $userId,
            'target_id' => $targetUserId,
            'status' => 'initiated',
        ];
    }

    public function acceptVideoCall(string $callId, int $userId): array
    {
        $callData = $this->redis->get("call:{$callId}");
        if (!$callData) {
            throw new \Exception('Call not found');
        }

        $call = json_decode($callData, true);
        if ($call['target_id'] !== $userId) {
            throw new \Exception('Unauthorized to accept this call');
        }

        // Update call status
        $call['status'] = 'accepted';
        $call['accepted_at'] = now()->toISOString();
        
        $this->redis->setex(
            "call:{$callId}",
            3600,
            json_encode($call)
        );

        // Publish call acceptance
        $this->redis->publish('video_call_updates', json_encode([
            'call_id' => $callId,
            'initiator_id' => $call['initiator_id'],
            'target_id' => $userId,
            'action' => 'accept',
            'timestamp' => now()->toISOString(),
        ]));

        return $call;
    }

    public function endVideoCall(string $callId, int $userId): void
    {
        $callData = $this->redis->get("call:{$callId}");
        if (!$callData) {
            return;
        }

        $call = json_decode($callData, true);
        if (!in_array($userId, [$call['initiator_id'], $call['target_id']])) {
            throw new \Exception('Unauthorized to end this call');
        }

        // Update call status
        $call['status'] = 'ended';
        $call['ended_at'] = now()->toISOString();
        
        $this->redis->setex(
            "call:{$callId}",
            3600,
            json_encode($call)
        );

        // Publish call end
        $this->redis->publish('video_call_updates', json_encode([
            'call_id' => $callId,
            'initiator_id' => $call['initiator_id'],
            'target_id' => $call['target_id'],
            'action' => 'end',
            'timestamp' => now()->toISOString(),
        ]));
    }
}
