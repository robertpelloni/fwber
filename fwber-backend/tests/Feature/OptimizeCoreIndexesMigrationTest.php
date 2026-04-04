<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OptimizeCoreIndexesMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_optimize_core_indexes_migration_is_idempotent(): void
    {
        $migration = require base_path('database/migrations/2026_04_03_212041_optimize_core_indexes.php');

        // The migration has already been applied by RefreshDatabase. Running it a
        // second time simulates the production deploy scenario where one or more
        // indexes already exist and the migration is retried.
        $migration->up();

        $this->assertTrue(true);
    }
}
