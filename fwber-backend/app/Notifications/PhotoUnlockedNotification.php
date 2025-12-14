<?php

namespace App\Notifications;

use App\Models\User;
use App\Models\Photo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PhotoUnlockedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $unlocker;
    protected $photo;

    /**
     * Create a new notification instance.
     */
    public function __construct(User $unlocker, Photo $photo)
    {
        $this->unlocker = $unlocker;
        $this->photo = $photo;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Photo Unlocked!',
            'message' => "{$this->unlocker->name} unlocked one of your private photos.",
            'unlocker_id' => $this->unlocker->id,
            'photo_id' => $this->photo->id,
            'type' => 'photo_unlock',
            'link' => "/profile/{$this->unlocker->id}", // Link to the unlocker's profile
        ];
    }
}
