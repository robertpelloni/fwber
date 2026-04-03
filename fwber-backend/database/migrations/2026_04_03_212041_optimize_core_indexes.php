<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        // 1. user_profiles
        Schema::table('user_profiles', function (Blueprint $table) use ($driver) {
            $table->index(['latitude', 'longitude'], 'idx_user_profiles_location');
            $table->index(['travel_latitude', 'travel_longitude'], 'idx_user_profiles_travel_location');
            $table->index('gender', 'idx_user_profiles_gender');
            $table->index('birthdate', 'idx_user_profiles_birthdate');
            $table->index('is_travel_mode', 'idx_user_profiles_travel_mode');
            
            // Composite index for fast matchmaking queries
            $table->index(['gender', 'birthdate'], 'idx_user_profiles_matchmaking');
        });

        // 2. user_matches
        Schema::table('user_matches', function (Blueprint $table) use ($driver) {
            // Already has unique constraint on [user1_id, user2_id]
            // We need a reverse lookup index for user2_id
            $table->index('user2_id', 'idx_user_matches_user2');
            $table->index('is_active', 'idx_user_matches_active');
            
            // Compound for the exact query: where user1 or user2 = X and active = 1
            $table->index(['user1_id', 'is_active'], 'idx_user_matches_u1_active');
            $table->index(['user2_id', 'is_active'], 'idx_user_matches_u2_active');
        });

        // 3. match_actions
        Schema::table('match_actions', function (Blueprint $table) use ($driver) {
            // Already has unique constraint on [user_id, target_user_id]
            // Needs reverse lookup for check for mutual matches
            $table->index('target_user_id', 'idx_match_actions_target');
            $table->index(['target_user_id', 'action'], 'idx_match_actions_target_action');
        });

        // 4. messages
        Schema::table('messages', function (Blueprint $table) use ($driver) {
            // Already has index on [sender_id, receiver_id]
            // Needs specific indexes for message retrieval and unread counts
            $table->index(['receiver_id', 'sender_id', 'created_at'], 'idx_msgs_conversation');
            $table->index(['receiver_id', 'read_at'], 'idx_msgs_unread');
            $table->index('created_at', 'idx_msgs_created');
        });

        // 5. photos
        Schema::table('photos', function (Blueprint $table) use ($driver) {
            // To quickly fetch a user's primary/ordered photos
            $table->index(['user_id', 'is_primary'], 'idx_photos_user_primary');
            $table->index(['user_id', 'order'], 'idx_photos_user_order');
        });

        // 6. proximity_artifacts
        Schema::table('proximity_artifacts', function (Blueprint $table) use ($driver) {
            $table->index('type', 'idx_artifacts_type');
            $table->index('expires_at', 'idx_artifacts_expiry');
            $table->index(['latitude', 'longitude'], 'idx_artifacts_location');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Out of scope for performance optimization rollback
    }
};
