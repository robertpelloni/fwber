<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations - Add composite indexes for common query patterns
     */
    public function up(): void
    {
        // Skip composite indexes on SQLite (testing environment)
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('user_profiles', function (Blueprint $table) {
            // Common filter combinations for matching algorithm
            $table->index(['gender', 'latitude', 'longitude'], 'profiles_match_filter_index');
        });

        Schema::table('messages', function (Blueprint $table) {
            // Conversation queries (sender + receiver + timestamp)
            $table->index(['sender_id', 'receiver_id', 'created_at'], 'messages_conversation_index');
            $table->index(['receiver_id', 'is_read', 'created_at'], 'messages_unread_index');
        });

        Schema::table('events', function (Blueprint $table) {
            // Event discovery queries (status + location + time)
            $table->index(['status', 'latitude', 'longitude'], 'events_discovery_index');
            $table->index(['created_by_user_id', 'status', 'starts_at'], 'events_creator_status_index');
        });

        Schema::table('user_matches', function (Blueprint $table) {
            // Match lookups (user pairs + active status)
            $table->index(['user1_id', 'user2_id', 'is_active'], 'matches_user_pair_index');
            $table->index(['is_active', 'last_message_at'], 'matches_active_recent_index');
        });

        Schema::table('chatroom_messages', function (Blueprint $table) {
            // Chatroom message queries
            $table->index(['chatroom_id', 'created_at', 'is_deleted'], 'chatroom_messages_feed_index');
        });

        Schema::table('proximity_chatroom_messages', function (Blueprint $table) {
            // Proximity chatroom message queries
            $table->index(['proximity_chatroom_id', 'created_at', 'is_deleted'], 'proximity_messages_feed_index');
            $table->index(['message_type', 'is_pinned'], 'proximity_messages_type_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Skip composite indexes on SQLite (testing environment)
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropIndex('profiles_match_filter_index');
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex('messages_conversation_index');
            $table->dropIndex('messages_unread_index');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex('events_discovery_index');
            $table->dropIndex('events_creator_status_index');
        });

        Schema::table('user_matches', function (Blueprint $table) {
            $table->dropIndex('matches_user_pair_index');
            $table->dropIndex('matches_active_recent_index');
        });

        Schema::table('chatroom_messages', function (Blueprint $table) {
            $table->dropIndex('chatroom_messages_feed_index');
        });

        Schema::table('proximity_chatroom_messages', function (Blueprint $table) {
            $table->dropIndex('proximity_messages_feed_index');
            $table->dropIndex('proximity_messages_type_index');
        });
    }
};
