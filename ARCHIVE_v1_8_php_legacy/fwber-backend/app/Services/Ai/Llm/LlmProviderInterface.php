<?php

namespace App\Services\Ai\Llm;

use App\DTOs\LlmResponse;

interface LlmProviderInterface
{
    /**
     * Send a chat completion request.
     *
     * @param  array  $messages  Array of ['role' => '...', 'content' => '...']
     * @param  array  $config  Configuration overrides (temperature, max_tokens, etc.)
     */
    public function chat(array $messages, array $config = []): LlmResponse;

    /**
     * Moderate content.
     *
     * @return array ['flagged' => bool, 'categories' => array, 'score' => float]
     */
    public function moderate(string $content): array;
}
