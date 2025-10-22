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
        // Enable PostGIS extension
        DB::statement('CREATE EXTENSION IF NOT EXISTS postgis');
        
        // Add geography columns to bulletin_boards table
        Schema::table('bulletin_boards', function (Blueprint $table) {
            $table->geography('location')->nullable()->comment('Center point as geography');
        });
        
        // Add geography columns to bulletin_messages table  
        Schema::table('bulletin_messages', function (Blueprint $table) {
            $table->geography('location')->nullable()->comment('Message location as geography');
        });
        
        // Create spatial indexes for efficient proximity queries
        DB::statement('CREATE INDEX IF NOT EXISTS idx_bulletin_boards_location_gist ON bulletin_boards USING GIST (location)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_bulletin_messages_location_gist ON bulletin_messages USING GIST (location)');
        
        // Backfill location data from existing lat/lng columns
        DB::statement("
            UPDATE bulletin_boards 
            SET location = ST_SetSRID(ST_MakePoint(center_lng, center_lat), 4326)::geography 
            WHERE center_lat IS NOT NULL AND center_lng IS NOT NULL AND location IS NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop spatial indexes
        DB::statement('DROP INDEX IF EXISTS idx_bulletin_boards_location_gist');
        DB::statement('DROP INDEX IF EXISTS idx_bulletin_messages_location_gist');
        
        // Remove geography columns
        Schema::table('bulletin_boards', function (Blueprint $table) {
            $table->dropColumn('location');
        });
        
        Schema::table('bulletin_messages', function (Blueprint $table) {
            $table->dropColumn('location');
        });
        
        // Note: We don't drop the PostGIS extension as it might be used by other parts of the system
    }
};
