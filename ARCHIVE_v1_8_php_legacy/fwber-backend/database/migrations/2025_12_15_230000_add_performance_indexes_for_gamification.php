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
        Schema::table('users', function (Blueprint $table) {
            $table->index('token_balance');
            $table->index('current_streak');
            $table->index('last_active_at');
        });

        Schema::table('match_assists', function (Blueprint $table) {
            $table->index(['status', 'matchmaker_id']); // Composite index for leaderboard query
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['token_balance']);
            $table->dropIndex(['current_streak']);
            $table->dropIndex(['last_active_at']);
        });

        Schema::table('match_assists', function (Blueprint $table) {
            $table->dropIndex(['status', 'matchmaker_id']);
        });
    }
};
