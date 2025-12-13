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
            $table->integer('db_query_count')->nullable()->after('duration_ms');
            $table->integer('memory_usage_kb')->nullable()->after('db_query_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('slow_requests', function (Blueprint $table) {
            $table->dropColumn(['db_query_count', 'memory_usage_kb']);
        });
    }
};
