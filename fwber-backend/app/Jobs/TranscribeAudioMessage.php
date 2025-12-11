<?php

namespace App\Jobs;

use App\Models\Message;
use App\Services\Ai\AudioTranscriptionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class TranscribeAudioMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public Message $message)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(AudioTranscriptionService $transcriptionService): void
    {
        if ($this->message->message_type !== 'audio' || !$this->message->media_url) {
            return;
        }

        try {
            // Parse the URL to get the path relative to storage root
            // media_url is like /storage/messages/1/abc.mp3
            $urlPath = parse_url($this->message->media_url, PHP_URL_PATH);
            
            // Remove leading /storage/ to get path relative to public disk
            $relativePath = preg_replace('/^\/?storage\//', '', $urlPath);
            
            $fullPath = Storage::disk('public')->path($relativePath);

            if (!file_exists($fullPath)) {
                Log::warning("Audio file not found for transcription job: {$fullPath}");
                return;
            }

            $text = $transcriptionService->transcribe($fullPath);

            if ($text) {
                $this->message->update(['transcription' => $text]);
            }
        } catch (\Exception $e) {
            Log::error("TranscribeAudioMessage job failed: " . $e->getMessage());
        }
    }
}
