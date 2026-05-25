<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds viewer_ip and user_agent columns for anonymous view tracking and analytics.
     */
    public function up(): void
    {
        Schema::table('profile_views', function (Blueprint $table) {
            if (! Schema::hasColumn('profile_views', 'viewer_ip')) {
                $table->string('viewer_ip', 45)->nullable()->after('viewer_user_id');
            }
            if (! Schema::hasColumn('profile_views', 'user_agent')) {
                $table->string('user_agent', 255)->nullable()->after('viewer_ip');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profile_views', function (Blueprint $table) {
            if (Schema::hasColumn('profile_views', 'viewer_ip')) {
                $table->dropColumn('viewer_ip');
            }
            if (Schema::hasColumn('profile_views', 'user_agent')) {
                $table->dropColumn('user_agent');
            }
        });
    }
};
