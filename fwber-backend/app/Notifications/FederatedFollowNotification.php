<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FederatedFollowNotification extends Notification
{
    use Queueable;

    protected $username;

    protected $domain;

    public function __construct($username, $domain)
    {
        $this->username = $username;
        $this->domain = $domain;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'friend_request',
            'title' => 'New Federated Follower',
            'message' => "@{$this->username}@{$this->domain} is now following you.",
            'user_name' => $this->username,
            'domain' => $this->domain,
        ];
    }
}
