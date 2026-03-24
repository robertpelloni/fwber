<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration neutralized on 2026-03-24 to resolve deployment blockage.
 * The 'deleted_at' column already exists in the 'proximity_artifacts' table.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Do nothing - column already exists.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Do nothing.
    }
};
