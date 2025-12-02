<?php

namespace App\Notifications;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EventReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $event;

    /**
     * Create a new notification instance.
     */
    public function __construct(Event $event)
    {
        $this->event = $event;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Reminder: Upcoming Event "' . $this->event->title . '"')
            ->line('This is a reminder that you have an upcoming event.')
            ->line('Event: ' . $this->event->title)
            ->line('Time: ' . $this->event->starts_at->format('F j, Y g:i A'))
            ->line('Location: ' . $this->event->location_name)
            ->action('View Event', url('/events/' . $this->event->id))
            ->line('We look forward to seeing you there!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'event_id' => $this->event->id,
            'title' => $this->event->title,
            'starts_at' => $this->event->starts_at,
            'message' => 'Reminder: Upcoming event "' . $this->event->title . '" starts soon.',
        ];
    }
}
