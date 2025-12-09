<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $indexExists = function ($table, $indexName) {
            $driver = DB::getDriverName();
            if ($driver === 'sqlite') {
                return collect(DB::select("SELECT name FROM sqlite_master WHERE type='index' AND tbl_name = ? AND name = ?", [$table, $indexName]))->count() > 0;
            }
            
            try {
                return collect(DB::select("SHOW INDEXES FROM " . $table . " WHERE Key_name = ?", [$indexName]))->count() > 0;
            } catch (\Exception $e) {
                return false;
            }
        };

        // Optimize Matches
        if (Schema::hasTable('matches')) {
            Schema::table('matches', function (Blueprint $table) use ($indexExists) {
                if (!$indexExists('matches', 'matches_user1_user2_index')) {
                    $table->index(['user1_id', 'user2_id'], 'matches_user1_user2_index');
                }
                if (!$indexExists('matches', 'matches_last_message_at_index')) {
                    $table->index('last_message_at', 'matches_last_message_at_index');
                }
            });
        }

        // Optimize Messages
        if (Schema::hasTable('messages')) {
            Schema::table('messages', function (Blueprint $table) use ($indexExists) {
                if (!$indexExists('messages', 'messages_sender_receiver_index')) {
                    $table->index(['sender_id', 'receiver_id'], 'messages_sender_receiver_index');
                }
                if (!$indexExists('messages', 'messages_sent_at_index')) {
                    $table->index('sent_at', 'messages_sent_at_index');
                }
                if (!$indexExists('messages', 'messages_is_read_index')) {
                    $table->index('is_read', 'messages_is_read_index');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('matches')) {
            Schema::table('matches', function (Blueprint $table) {
                $table->dropIndex('matches_user1_user2_index');
                $table->dropIndex('matches_last_message_at_index');
            });
        }

        if (Schema::hasTable('messages')) {
            Schema::table('messages', function (Blueprint $table) {
                $table->dropIndex('messages_sender_receiver_index');
                $table->dropIndex('messages_sent_at_index');
                $table->dropIndex('messages_is_read_index');
            });
        }
    }
};
