<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GroupMemberMuted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $groupId,
        public int $actorUserId,
        public int $targetUserId,
        public string $mutedUntilIso,
        public ?string $reason,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('groups.'.$this->groupId);
    }
}
