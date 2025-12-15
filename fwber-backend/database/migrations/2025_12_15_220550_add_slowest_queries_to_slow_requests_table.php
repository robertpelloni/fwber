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
        Schema::table('slow_requests', function (Blueprint $table) {
            $table->json('slowest_queries')->nullable()->after('memory_usage_kb');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('slow_requests', function (Blueprint $table) {
            $table->dropColumn('slowest_queries');
        });
    }
};
