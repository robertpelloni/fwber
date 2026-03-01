<?php

namespace App\Events;

use App\Models\AudioRoom;
use App\Models\AudioRoomParticipant;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AudioRoomParticipantJoined implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $room;
    public $participant;

    /**
     * Create a new event instance.
     */
    public function __construct(AudioRoom $room, AudioRoomParticipant $participant)
    {
        $this->room = $room;
        $this->participant = $participant;
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
        return 'ParticipantJoined';
    }
}
