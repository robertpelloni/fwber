<?php

namespace Tests\Traits;

use Illuminate\Foundation\Testing\RefreshDatabase as BaseRefreshDatabase;

/**
 * Custom RefreshDatabase trait that overrides Laravel's default behavior
 * to prevent interactive confirmation prompts during test migrations.
 * 
 * This is necessary in Laravel 12 where production safety confirmations
 * can trigger even in test environments, causing Mockery errors.
 * 
 * Usage: Replace `use RefreshDatabase;` with `use RefreshDatabaseSilently;`
 */
trait RefreshDatabaseSilently
{
    use BaseRefreshDatabase {
        refreshDatabase as baseRefreshDatabase;
    }

    /**
     * Override refreshDatabase to run migrations without confirmation.
     */
    protected function refreshDatabase(): void
    {
        // Run migrations programmatically without console confirmation
        $this->artisan('migrate:fresh', ['--force' => true])->run();
    }
}
