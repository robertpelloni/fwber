<?php

namespace App\Notifications;

use App\Models\Photo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class AvatarGeneratedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $photo;

    public function __construct(Photo $photo)
    {
        $this->photo = $photo;
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    public function toArray($notifiable)
    {
        return [
            'type' => 'avatar_generated',
            'photo_id' => $this->photo->id,
            'url' => $this->photo->url, // Accessor or file_path
            'message' => 'Your AI avatar is ready!',
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'type' => 'avatar_generated',
            'photo_id' => $this->photo->id,
            'url' => $this->photo->url,
            'message' => 'Your AI avatar is ready!',
        ]);
    }
}
