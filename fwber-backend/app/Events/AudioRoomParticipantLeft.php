<?php

namespace App\Events;

use App\Models\AudioRoom;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AudioRoomParticipantLeft implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $room;
    public $user_id;

    /**
     * Create a new event instance.
     */
    public function __construct(AudioRoom $room, $user_id)
    {
        $this->room = $room;
        $this->user_id = $user_id;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PresenceChannel('audio-rooms.' . $this->room->id),
        ];
    }

    public function broadcastAs()
    {
        return 'ParticipantLeft';
    }
}
