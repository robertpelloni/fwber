<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $senderId;
    public $recipientId;
    public $message;

    /**
     * Create a new event instance.
     */
    public function __construct($senderId, $recipientId, $message)
    {
        $this->senderId = $senderId;
        $this->recipientId = $recipientId;
        $this->message = $message;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('users.' . $this->recipientId),
            new PrivateChannel('users.' . $this->senderId),
        ];
    }

    public function broadcastAs()
    {
        return 'ChatMessageSent';
    }
}
