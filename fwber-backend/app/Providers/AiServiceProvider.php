<?php

namespace App\Providers;

use App\Services\Ai\Llm\LlmManager;
use Illuminate\Support\ServiceProvider;

class AiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LlmManager::class, function ($app) {
            return new LlmManager();
        });
    }

    public function boot(): void
    {
        //
    }
}
