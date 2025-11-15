<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GroupOwnershipTransferred implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $groupId,
        public int $fromUserId,
        public int $toUserId,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('groups.'.$this->groupId);
    }

    public function broadcastAs(): string
    {
        return 'group.ownership.transferred';
    }

    public function broadcastWith(): array
    {
        return [
            'group_id' => $this->groupId,
            'from_user_id' => $this->fromUserId,
            'to_user_id' => $this->toUserId,
        ];
    }
}
