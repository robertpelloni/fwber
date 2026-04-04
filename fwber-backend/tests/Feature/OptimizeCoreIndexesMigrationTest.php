<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
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

    public function test_optimize_core_indexes_migration_skips_indexes_for_missing_columns(): void
    {
        Schema::dropIfExists('photos');
        Schema::create('photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->string('storage_path');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });

        $migration = require base_path('database/migrations/2026_04_03_212041_optimize_core_indexes.php');
        $migration->up();

        $this->assertTrue(true);
    }
}
