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
        // Audit and optimize indexes for high-traffic tables

        Schema::table('messages', function (Blueprint $table) {
            try {
                $table->index(['sender_id', 'receiver_id', 'created_at'], 'messages_sender_receiver_created_index');
            } catch (\Exception $e) {
                // Index likely exists
            }
        });

        Schema::table('matches', function (Blueprint $table) {
            try {
                $table->index(['user_id', 'matched_user_id', 'status'], 'matches_users_status_index');
            } catch (\Exception $e) {
                // Index likely exists
            }
        });

        Schema::table('profile_views', function (Blueprint $table) {
            try {
                $table->index(['viewed_id', 'viewer_id'], 'profile_views_viewed_viewer_index');
            } catch (\Exception $e) {
                // Index likely exists
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex('messages_sender_receiver_created_index');
        });

        Schema::table('matches', function (Blueprint $table) {
            $table->dropIndex('matches_users_status_index');
        });

        Schema::table('profile_views', function (Blueprint $table) {
            $table->dropIndex('profile_views_viewed_viewer_index');
        });
    }
};
