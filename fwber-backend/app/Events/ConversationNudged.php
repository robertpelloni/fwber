<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConversationNudged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $receiverId;
    public int $senderId;
    public array $nudge;

    /**
     * Create a new event instance.
     *
     * @param int $receiverId The user who will see the nudge
     * @param int $senderId The user they are talking to
     * @param array $nudge The nudge data ['type', 'message']
     */
    public function __construct(int $receiverId, int $senderId, array $nudge)
    {
        $this->receiverId = $receiverId;
        $this->senderId = $senderId;
        $this->nudge = $nudge;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("users.{$this->receiverId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'ConversationNudged';
    }
}
