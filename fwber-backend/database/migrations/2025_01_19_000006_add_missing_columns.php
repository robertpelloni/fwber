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
        // Add spatial columns to bulletin_boards table (MySQL compatible)
        if (!Schema::hasColumn('bulletin_boards', 'location')) {
            DB::statement('ALTER TABLE bulletin_boards ADD COLUMN location POINT NULL COMMENT "Center point as spatial data"');
        }
        
        // Add missing columns to bulletin_boards table
        Schema::table('bulletin_boards', function (Blueprint $table) {
            if (!Schema::hasColumn('bulletin_boards', 'name')) {
                $table->string('name')->nullable();
            }
            if (!Schema::hasColumn('bulletin_boards', 'description')) {
                $table->text('description')->nullable();
            }
            if (!Schema::hasColumn('bulletin_boards', 'radius_meters')) {
                $table->integer('radius_meters')->default(1000);
            }
            if (!Schema::hasColumn('bulletin_boards', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
            if (!Schema::hasColumn('bulletin_boards', 'last_activity_at')) {
                $table->timestamp('last_activity_at')->nullable();
            }
            if (!Schema::hasColumn('bulletin_boards', 'message_count')) {
                $table->integer('message_count')->default(0);
            }
            if (!Schema::hasColumn('bulletin_boards', 'active_users')) {
                $table->integer('active_users')->default(0);
            }
        });
        
        // Add spatial columns to bulletin_messages table (MySQL compatible)
        if (!Schema::hasColumn('bulletin_messages', 'location')) {
            DB::statement('ALTER TABLE bulletin_messages ADD COLUMN location POINT NULL COMMENT "Message location as spatial data"');
        }
        
        // Add missing columns to bulletin_messages table
        Schema::table('bulletin_messages', function (Blueprint $table) {
            if (!Schema::hasColumn('bulletin_messages', 'is_anonymous')) {
                $table->boolean('is_anonymous')->default(false);
            }
            if (!Schema::hasColumn('bulletin_messages', 'expires_at')) {
                $table->timestamp('expires_at')->nullable();
            }
            if (!Schema::hasColumn('bulletin_messages', 'is_moderated')) {
                $table->boolean('is_moderated')->default(false);
            }
        });
        
        // Create spatial indexes for efficient proximity queries (MySQL syntax)
        try {
            DB::statement('CREATE SPATIAL INDEX idx_bulletin_boards_location ON bulletin_boards (location)');
        } catch (\Exception $e) {
            // Index might already exist
        }
        
        try {
            DB::statement('CREATE SPATIAL INDEX idx_bulletin_messages_location ON bulletin_messages (location)');
        } catch (\Exception $e) {
            // Index might already exist
        }
        
        // Backfill location data from existing lat/lng columns
        DB::statement("
            UPDATE bulletin_boards 
            SET location = POINT(center_lng, center_lat) 
            WHERE center_lat IS NOT NULL AND center_lng IS NOT NULL AND location IS NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop spatial indexes
        try {
            DB::statement('DROP INDEX idx_bulletin_boards_location ON bulletin_boards');
        } catch (\Exception $e) {
            // Index might not exist
        }
        
        try {
            DB::statement('DROP INDEX idx_bulletin_messages_location ON bulletin_messages');
        } catch (\Exception $e) {
            // Index might not exist
        }
        
        // Remove spatial columns and other added columns
        Schema::table('bulletin_boards', function (Blueprint $table) {
            $table->dropColumn([
                'location', 'name', 'description', 'radius_meters', 
                'is_active', 'last_activity_at', 'message_count', 'active_users'
            ]);
        });
        
        Schema::table('bulletin_messages', function (Blueprint $table) {
            $table->dropColumn(['location', 'is_anonymous', 'expires_at', 'is_moderated']);
        });
    }
};
