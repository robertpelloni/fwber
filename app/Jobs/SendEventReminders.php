<?php

namespace App\Jobs;

use App\Models\Event;
use App\Models\EventAttendee;
use App\Models\User;
use App\Notifications\EventReminderNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SendEventReminders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 120;

    /**
     * The name of the queue the job should be sent to.
     *
     * @var string|null
     */
    public $queue = 'notifications';

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * Send reminders for events starting within the next 24 hours.
     */
    public function handle(): void
    {
        $now = Carbon::now();
        $reminderWindow = $now->copy()->addHours(24);

        // Find all upcoming events starting within the next 24 hours
        $upcomingEvents = Event::where('status', 'upcoming')
            ->whereBetween('starts_at', [$now, $reminderWindow])
            ->where('reminder_sent', false)
            ->get();

        $count = $upcomingEvents->count();

        if ($count === 0) {
            Log::info('SendEventReminders: No events require reminders');
            return;
        }

        foreach ($upcomingEvents as $event) {
            // Get all attendees who RSVPed as 'attending'
            $attendees = EventAttendee::where('event_id', $event->id)
                ->where('status', 'attending')
                ->with('user')
                ->get();

            foreach ($attendees as $attendee) {
                if ($attendee->user) {
                    try {
                        $attendee->user->notify(new EventReminderNotification($event));
                        Log::info("SendEventReminders: Sent reminder to user {$attendee->user_id} for event {$event->id} '{$event->title}'");
                    } catch (\Exception $e) {
                        Log::error("SendEventReminders: Failed to send reminder to user {$attendee->user_id} for event {$event->id}: " . $e->getMessage());
                    }
                }
            }

            // Mark reminder as sent
            $event->reminder_sent = true;
            $event->save();

            Log::info("SendEventReminders: Completed reminders for event {$event->id} to {$attendees->count()} attendee(s)");
        }

        Log::info("SendEventReminders: Processed {$count} upcoming event(s)");
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendEventReminders job failed: ' . $exception->getMessage());
    }
}
