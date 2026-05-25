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
            $table->string('route_name')->nullable()->after('url');
            $table->string('action')->nullable()->after('route_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('slow_requests', function (Blueprint $table) {
            $table->dropColumn(['route_name', 'action']);
        });
    }
};
