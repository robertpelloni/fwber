<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Conditionally adds flag columns to proximity_artifacts for test database compatibility.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('proximity_artifacts', function (Blueprint $table) {
            if (! Schema::hasColumn('proximity_artifacts', 'is_flagged')) {
                $table->boolean('is_flagged')->default(false);
            }
            if (! Schema::hasColumn('proximity_artifacts', 'flag_count')) {
                $table->integer('flag_count')->default(0);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proximity_artifacts', function (Blueprint $table) {
            if (Schema::hasColumn('proximity_artifacts', 'is_flagged')) {
                $table->dropColumn('is_flagged');
            }
            if (Schema::hasColumn('proximity_artifacts', 'flag_count')) {
                $table->dropColumn('flag_count');
            }
        });
    }
};
