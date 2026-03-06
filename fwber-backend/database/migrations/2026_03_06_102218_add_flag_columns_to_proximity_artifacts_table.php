<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('proximity_artifacts', function (Blueprint $table) {
            $table->boolean('is_flagged')->default(false);
            $table->unsignedInteger('flag_count')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proximity_artifacts', function (Blueprint $table) {
            $table->dropColumn(['is_flagged', 'flag_count']);
        });
    }
};
