<?php

namespace App\DTOs;

class LlmResponse
{
    public function __construct(
        public string $content,
        public string $provider,
        public ?array $metadata = []
    ) {}
}
