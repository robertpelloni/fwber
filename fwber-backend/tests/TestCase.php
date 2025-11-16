<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureDatabaseReady();
    }

    protected function ensureDatabaseReady(): void
    {
        try {
            // Ensure app runs in testing context to avoid production confirmations
            config(['app.env' => 'testing']);
            // Bypass interactive console prompts in tests
            \Illuminate\Support\Facades\Artisan::call('migrate:fresh', ['--force' => true]);
        } catch (\Throwable $exception) {
            $message = strtolower($exception->getMessage());

            if (str_contains($message, "could not find driver")) {
                $this->markTestSkipped("Database driver unavailable: " . $exception->getMessage());

                return;
            }

            throw $exception;
        }
    }

    public function createApplication()
    {
        $app = require __DIR__ . "/../bootstrap/app.php";

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
}
