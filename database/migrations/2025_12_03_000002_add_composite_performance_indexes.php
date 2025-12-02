<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

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

        $addIndex = function($table, $columns, $name) {
            if (Schema::hasTable($table)) {
                // Check if index exists using raw SQL for MySQL to avoid duplicate key errors
                $exists = count(DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$name])) > 0;
                
                if (!$exists) {
                    try {
                        Schema::table($table, function (Blueprint $table) use ($columns, $name) {
                            $table->index($columns, $name);
                        });
                    } catch (\Exception $e) {
                        // Index likely exists
                    }
                }
            }
        };

        // User Profiles
        $addIndex('user_profiles', ['gender', 'latitude', 'longitude'], 'profiles_match_filter_index');

        // Messages
        $addIndex('messages', ['sender_id', 'receiver_id', 'created_at'], 'messages_conversation_index');
        $addIndex('messages', ['receiver_id', 'is_read', 'created_at'], 'messages_unread_index');

        // Events
        $addIndex('events', ['status', 'latitude', 'longitude'], 'events_discovery_index');
        $addIndex('events', ['created_by_user_id', 'status', 'starts_at'], 'events_creator_status_index');

        // User Matches
        $addIndex('user_matches', ['user1_id', 'user2_id', 'is_active'], 'matches_user_pair_index');
        $addIndex('user_matches', ['is_active', 'last_message_at'], 'matches_active_recent_index');

        // Chatroom Messages
        $addIndex('chatroom_messages', ['chatroom_id', 'created_at', 'is_deleted'], 'chatroom_messages_feed_index');

        // Proximity Chatroom Messages
        $addIndex('proximity_chatroom_messages', ['proximity_chatroom_id', 'created_at', 'is_deleted'], 'proximity_messages_feed_index');
        $addIndex('proximity_chatroom_messages', ['message_type', 'is_pinned'], 'proximity_messages_type_index');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        $dropIndex = function($table, $name) {
            if (Schema::hasTable($table)) {
                try {
                    Schema::table($table, function (Blueprint $table) use ($name) {
                        $table->dropIndex($name);
                    });
                } catch (\Exception $e) {}
            }
        };

        $dropIndex('user_profiles', 'profiles_match_filter_index');
        $dropIndex('messages', 'messages_conversation_index');
        $dropIndex('messages', 'messages_unread_index');
        $dropIndex('events', 'events_discovery_index');
        $dropIndex('events', 'events_creator_status_index');
        $dropIndex('user_matches', 'matches_user_pair_index');
        $dropIndex('user_matches', 'matches_active_recent_index');
        $dropIndex('chatroom_messages', 'chatroom_messages_feed_index');
        $dropIndex('proximity_chatroom_messages', 'proximity_messages_feed_index');
        $dropIndex('proximity_chatroom_messages', 'proximity_messages_type_index');
    }
};
