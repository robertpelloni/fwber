<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class AudioTranscriptionService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://api.openai.com/v1';

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key') ?? '';
    }

    public function transcribe(string $filePath): ?string
    {
        if (!file_exists($filePath)) {
            Log::error("Audio file not found for transcription: {$filePath}");
            return null;
        }

        try {
            $response = Http::withToken($this->apiKey)
                ->attach('file', fopen($filePath, 'r'), basename($filePath))
                ->attach('model', 'whisper-1')
                ->post("{$this->baseUrl}/audio/transcriptions");

            if ($response->successful()) {
                return $response->json('text');
            }

            Log::error('OpenAI Transcription failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (Exception $e) {
            Log::error('Audio transcription exception', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
