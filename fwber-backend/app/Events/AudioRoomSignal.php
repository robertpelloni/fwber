<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AudioRoomSignal implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $room_id;
    public $sender_id;
    public $target_id;
    public $type;
    public $payload;

    /**
     * Create a new event instance.
     */
    public function __construct($room_id, $sender_id, $target_id, $type, $payload)
    {
        $this->room_id = $room_id;
        $this->sender_id = $sender_id;
        $this->target_id = $target_id;
        $this->type = $type;
        $this->payload = $payload;
    }

    /**
     * Get the channels the event should broadcast on.
     * We broadcast directly to the target user's private channel.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('users.' . $this->target_id),
        ];
    }

    public function broadcastAs()
    {
        return 'AudioRoomSignal';
    }
}
