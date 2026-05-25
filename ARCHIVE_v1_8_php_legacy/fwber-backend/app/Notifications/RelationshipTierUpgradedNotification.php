<?php

namespace App\Notifications;

use App\Models\RelationshipTier;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class RelationshipTierUpgradedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $tier;

    protected $otherUser;

    /**
     * Create a new notification instance.
     */
    public function __construct(RelationshipTier $tier, User $otherUser)
    {
        $this->tier = $tier;
        $this->otherUser = $otherUser;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $tierInfo = $this->tier->getTierInfo();

        return [
            'type' => 'tier_upgraded',
            'match_id' => $this->tier->match_id,
            'other_user_id' => $this->otherUser->id,
            'other_user_name' => $this->otherUser->name,
            'new_tier' => $this->tier->current_tier,
            'tier_name' => $tierInfo['name'],
            'tier_icon' => $tierInfo['icon'],
            'unlocks' => $tierInfo['unlocks'],
            'message' => "Your connection with {$this->otherUser->name} has leveled up to {$tierInfo['name']}! {$tierInfo['icon']}",
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
