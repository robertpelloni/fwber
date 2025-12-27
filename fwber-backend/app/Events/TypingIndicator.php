<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TypingIndicator implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $from_user_id;
    public $to_user_id;
    public $chatroom_id;
    public $is_typing;

    /**
     * Create a new event instance.
     */
    public function __construct($from_user_id, $to_user_id, $chatroom_id, $is_typing)
    {
        $this->from_user_id = $from_user_id;
        $this->to_user_id = $to_user_id;
        $this->chatroom_id = $chatroom_id;
        $this->is_typing = $is_typing;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        if ($this->chatroom_id) {
            return [new PrivateChannel('chatroom.' . $this->chatroom_id)];
        }
        
        if ($this->to_user_id) {
            return [new PrivateChannel('users.' . $this->to_user_id)];
        }

        return [];
    }

    public function broadcastAs()
    {
        return 'TypingIndicator';
    }
}
