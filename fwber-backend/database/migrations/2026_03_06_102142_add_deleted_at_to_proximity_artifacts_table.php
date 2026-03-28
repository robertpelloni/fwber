<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Conditionally adds deleted_at to proximity_artifacts for test database compatibility.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('proximity_artifacts', function (Blueprint $table) {
            if (! Schema::hasColumn('proximity_artifacts', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proximity_artifacts', function (Blueprint $table) {
            if (Schema::hasColumn('proximity_artifacts', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
