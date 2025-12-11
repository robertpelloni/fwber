<?php

namespace App\Services;

use App\Services\Ai\Llm\LlmManager;
use Illuminate\Support\Facades\Log;

class TranslationService
{
    protected LlmManager $llmManager;

    public function __construct(LlmManager $llmManager)
    {
        $this->llmManager = $llmManager;
    }

    /**
     * Translate text to the target language.
     *
     * @param string $text The text to translate
     * @param string $targetLanguage The target language code (e.g., 'es', 'fr', 'en')
     * @return string The translated text
     */
    public function translate(string $text, string $targetLanguage): string
    {
        if (empty(trim($text))) {
            return "";
        }

        // Simple cache key based on text and target language
        // In a real app, we'd use a proper caching layer here
        
        $prompt = "Translate the following text to {$targetLanguage}. Return ONLY the translated text, no explanations or quotes.\n\nText: {$text}";

        try {
            $response = $this->llmManager->driver()->chat([
                ['role' => 'system', 'content' => 'You are a professional translator. Translate the user input accurately to the target language. Do not add any conversational filler.'],
                ['role' => 'user', 'content' => $prompt]
            ], ['temperature' => 0.3]);

            return trim($response->content);
        } catch (\Exception $e) {
            Log::error("TranslationService: Failed to translate: " . $e->getMessage());
            return $text; // Fallback to original
        }
    }
}
