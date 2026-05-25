<?php

namespace App\Services\Ai\Llm;

use App\Services\Ai\Llm\Drivers\AnthropicLlmDriver;
use App\Services\Ai\Llm\Drivers\GeminiLlmDriver;
use App\Services\Ai\Llm\Drivers\OpenAiLlmDriver;
use InvalidArgumentException;

class LlmManager
{
    private array $drivers = [];

    public function driver(string $name = 'openai'): LlmProviderInterface
    {
        if (isset($this->drivers[$name])) {
            return $this->drivers[$name];
        }

        return $this->drivers[$name] = $this->resolve($name);
    }

    private function resolve(string $name): LlmProviderInterface
    {
        return match ($name) {
            'openai' => $this->createOpenAiDriver(),
            'gemini' => $this->createGeminiDriver(),
            'claude', 'anthropic' => $this->createAnthropicDriver(),
            default => throw new InvalidArgumentException("LLM driver [{$name}] is not supported."),
        };
    }

    private function createOpenAiDriver(): OpenAiLlmDriver
    {
        return new OpenAiLlmDriver(
            apiKey: config('services.openai.api_key') ?? '',
            model: config('services.openai.model', 'gpt-4')
        );
    }

    private function createGeminiDriver(): GeminiLlmDriver
    {
        return new GeminiLlmDriver(
            apiKey: config('services.gemini.api_key') ?? '',
            model: config('services.gemini.model', 'gemini-pro')
        );
    }

    private function createAnthropicDriver(): AnthropicLlmDriver
    {
        return new AnthropicLlmDriver(
            apiKey: config('services.anthropic.api_key') ?? '',
            model: config('services.anthropic.model', 'claude-3-5-sonnet-20241022')
        );
    }
}
