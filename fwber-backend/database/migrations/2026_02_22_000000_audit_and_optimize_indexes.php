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
        // Optimize 'messages' table for conversation retrieval
        Schema::table('messages', function (Blueprint $table) {
            if (!Schema::hasIndex('messages', ['sender_id', 'receiver_id', 'created_at'])) {
                $table->index(['sender_id', 'receiver_id', 'created_at'], 'messages_sender_receiver_created_index');
            }
            if (!Schema::hasIndex('messages', ['receiver_id', 'sender_id', 'created_at'])) {
                $table->index(['receiver_id', 'sender_id', 'created_at'], 'messages_receiver_sender_created_index');
            }
        });

        // Optimize 'profile_views' for dashboard stats
        Schema::table('profile_views', function (Blueprint $table) {
            if (!Schema::hasIndex('profile_views', ['viewed_user_id', 'created_at'])) {
                $table->index(['viewed_user_id', 'created_at'], 'profile_views_viewed_user_created_index');
            }
        });

        // Optimize 'matches' for active match queries
        Schema::table('matches', function (Blueprint $table) {
            if (!Schema::hasIndex('matches', ['user1_id', 'status'])) {
                $table->index(['user1_id', 'status'], 'matches_user1_status_index');
            }
            if (!Schema::hasIndex('matches', ['user2_id', 'status'])) {
                $table->index(['user2_id', 'status'], 'matches_user2_status_index');
            }
        });

        // Optimize 'notifications' for unread counts
        Schema::table('notifications', function (Blueprint $table) {
             if (!Schema::hasIndex('notifications', ['notifiable_id', 'notifiable_type', 'read_at'])) {
                $table->index(['notifiable_id', 'notifiable_type', 'read_at'], 'notifications_notifiable_read_index');
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
            $table->dropIndex('messages_receiver_sender_created_index');
        });

        Schema::table('profile_views', function (Blueprint $table) {
            $table->dropIndex('profile_views_viewed_user_created_index');
        });

        Schema::table('matches', function (Blueprint $table) {
            $table->dropIndex('matches_user1_status_index');
            $table->dropIndex('matches_user2_status_index');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_notifiable_read_index');
        });
    }
};
