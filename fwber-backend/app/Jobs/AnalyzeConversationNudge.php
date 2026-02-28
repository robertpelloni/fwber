<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\Message;
use App\Services\AiWingmanService;
use App\Events\ConversationNudged;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AnalyzeConversationNudge implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $receiverId;
    public int $senderId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $receiverId, int $senderId)
    {
        $this->receiverId = $receiverId;
        $this->senderId = $senderId;
    }

    /**
     * Execute the job.
     */
    public function handle(AiWingmanService $wingmanService): void
    {
        try {
            $user = User::find($this->receiverId);
            $match = User::find($this->senderId);

            if (!$user || !$match) {
                return;
            }

            // Fetch recent history (last 15 messages)
            $messages = Message::where(function($q) use ($user, $match) {
                    $q->where('sender_id', $user->id)->where('receiver_id', $match->id);
                })
                ->orWhere(function($q) use ($user, $match) {
                    $q->where('sender_id', $match->id)->where('receiver_id', $user->id);
                })
                ->orderBy('created_at', 'asc') // Oldest first for context narrative
                ->take(15)
                ->get()
                ->map(function($msg) {
                    return [
                        'sender_id' => $msg->sender_id,
                        'content' => $msg->content,
                        'transcription' => $msg->transcription,
                    ];
                })
                ->toArray();

            if (count($messages) < 5) {
                return; // Not enough context
            }

            $nudge = $wingmanService->generateProactiveNudge($user, $match, $messages);

            // If the service determined a nudge is needed, broadcast it
            if ($nudge) {
                broadcast(new ConversationNudged($user->id, $match->id, $nudge));
            }

        } catch (\Exception $e) {
            Log::error("AnalyzeConversationNudge Job Failed: " . $e->getMessage());
        }
    }
}
