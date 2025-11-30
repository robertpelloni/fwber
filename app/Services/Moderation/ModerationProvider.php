<?php

namespace App\Services\Moderation;

interface ModerationProvider
{
    /**
     * @return array{flagged:bool,categories:array<string,float>,reason?:string,confidence?:float,provider:string}
     */
    public function moderate(string $content, array $context = []): array;
}
