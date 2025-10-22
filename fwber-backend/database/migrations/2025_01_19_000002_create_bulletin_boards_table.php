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
        Schema::create('bulletin_boards', function (Blueprint $table) {
            $table->id();
            $table->string('geohash', 10)->index(); // Geohash for location-based partitioning
            $table->decimal('center_lat', 10, 8); // Center latitude of the board area
            $table->decimal('center_lng', 11, 8); // Center longitude of the board area
            $table->integer('radius_meters')->default(1000); // Radius in meters for this board
            $table->string('name')->nullable(); // Optional name for the board
            $table->text('description')->nullable(); // Optional description
            $table->boolean('is_active')->default(true); // Whether the board is active
            $table->integer('message_count')->default(0); // Cached message count
            $table->integer('active_users')->default(0); // Cached active user count
            $table->timestamp('last_activity_at')->nullable(); // Last message timestamp
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['geohash', 'is_active']);
            $table->index(['center_lat', 'center_lng']);
            $table->index('last_activity_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bulletin_boards');
    }
};
