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
        Schema::create('proximity_chatrooms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['conference', 'event', 'venue', 'area', 'temporary'])->default('area');
            $table->string('venue_name')->nullable();
            $table->string('venue_type')->nullable(); // bar, club, conference, restaurant, etc.
            $table->string('event_name')->nullable();
            $table->date('event_date')->nullable();
            $table->time('event_start_time')->nullable();
            $table->time('event_end_time')->nullable();
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->integer('radius_meters')->default(100); // Proximity radius
            $table->string('geohash', 12); // For efficient proximity queries
            $table->string('city')->nullable();
            $table->string('neighborhood')->nullable();
            $table->string('address')->nullable();
            $table->json('tags')->nullable(); // Professional interests, topics, etc.
            $table->json('settings')->nullable();
            $table->bigInteger('created_by')->unsigned();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_public')->default(true);
            $table->boolean('requires_approval')->default(false);
            $table->integer('max_members')->nullable();
            $table->integer('current_members')->default(0);
            $table->integer('message_count')->default(0);
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('expires_at')->nullable(); // For temporary chatrooms
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->index(['latitude', 'longitude']);
            $table->index('geohash');
            $table->index('type');
            $table->index('venue_type');
            $table->index('is_active');
            $table->index('expires_at');
            $table->index('last_activity_at');
        });

        // Create spatial index for proximity queries (MySQL compatible)
        DB::statement('CREATE SPATIAL INDEX idx_proximity_chatrooms_location ON proximity_chatrooms (POINT(longitude, latitude))');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proximity_chatrooms');
    }
};
