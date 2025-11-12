<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GroupMemberBanned implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $groupId,
        public int $actorUserId,
        public int $targetUserId,
        public ?string $reason,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('groups.'.$this->groupId);
    }

    public function broadcastAs(): string
    {
        return 'group.member.banned';
    }

    public function broadcastWith(): array
    {
        return [
            'group_id' => $this->groupId,
            'actor_user_id' => $this->actorUserId,
            'target_user_id' => $this->targetUserId,
            'reason' => $this->reason,
        ];
    }
}
