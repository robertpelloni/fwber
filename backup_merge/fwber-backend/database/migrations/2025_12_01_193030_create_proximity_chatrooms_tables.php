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
        if (!Schema::hasTable('proximity_chatrooms')) {
            Schema::create('proximity_chatrooms', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('type')->default('area'); // conference, event, venue, area, temporary
                
                // Venue/Event Info
                $table->string('venue_name')->nullable();
                $table->string('venue_type')->nullable();
                $table->string('event_name')->nullable();
                $table->date('event_date')->nullable();
                $table->time('event_start_time')->nullable();
                $table->time('event_end_time')->nullable();
                
                // Location
                $table->decimal('latitude', 10, 8);
                $table->decimal('longitude', 11, 8);
                $table->integer('radius_meters')->default(1000);
                $table->string('geohash')->nullable()->index();
                $table->string('city')->nullable()->index();
                $table->string('neighborhood')->nullable();
                $table->string('address')->nullable();
                
                // Metadata
                $table->json('tags')->nullable();
                $table->json('settings')->nullable();
                $table->foreignId('created_by')->constrained('users');
                
                // Status
                $table->boolean('is_active')->default(true);
                $table->boolean('is_public')->default(true);
                $table->boolean('requires_approval')->default(false);
                
                // Stats
                $table->integer('max_members')->default(1000);
                $table->integer('current_members')->default(0);
                $table->integer('message_count')->default(0);
                
                $table->timestamp('last_activity_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
                
                // Indexes for geospatial search
                $table->index(['latitude', 'longitude']);
            });
        }

        if (!Schema::hasTable('proximity_chatroom_members')) {
            Schema::create('proximity_chatroom_members', function (Blueprint $table) {
                $table->id();
                $table->foreignId('proximity_chatroom_id')->constrained()->onDelete('cascade');
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                
                $table->string('role')->default('member'); // member, moderator, admin
                $table->boolean('is_muted')->default(false);
                $table->boolean('is_banned')->default(false);
                
                // Member Location Context
                $table->decimal('latitude', 10, 8)->nullable();
                $table->decimal('longitude', 11, 8)->nullable();
                $table->integer('distance_meters')->nullable();
                
                // Timestamps
                $table->timestamp('joined_at')->useCurrent();
                $table->timestamp('last_seen_at')->useCurrent();
                $table->timestamp('last_location_update')->nullable();
                
                // Profile Context
                $table->json('preferences')->nullable();
                $table->json('professional_info')->nullable();
                $table->json('interests')->nullable();
                
                // Visibility
                $table->boolean('is_visible')->default(true);
                $table->boolean('is_networking')->default(false);
                $table->boolean('is_social')->default(true);
                
                $table->timestamps();
                
                $table->unique(['proximity_chatroom_id', 'user_id']);
            });
        }

        if (!Schema::hasTable('proximity_chatroom_messages')) {
            Schema::create('proximity_chatroom_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('proximity_chatroom_id')->constrained()->onDelete('cascade');
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('parent_id')->nullable()->constrained('proximity_chatroom_messages')->onDelete('cascade');
                
                $table->text('content');
                $table->string('message_type')->default('text'); // text, image, location, system
                $table->json('metadata')->nullable();
                
                // Flags
                $table->boolean('is_edited')->default(false);
                $table->boolean('is_deleted')->default(false);
                $table->boolean('is_pinned')->default(false);
                $table->boolean('is_announcement')->default(false);
                $table->boolean('is_networking')->default(false);
                $table->boolean('is_social')->default(false);
                
                // Stats
                $table->integer('reaction_count')->default(0);
                $table->integer('reply_count')->default(0);
                
                $table->timestamp('edited_at')->nullable();
                $table->timestamp('deleted_at')->nullable();
                $table->timestamps();
                
                $table->index(['proximity_chatroom_id', 'created_at'], 'pcm_chatroom_created_index');
            });
        }

        if (!Schema::hasTable('proximity_chatroom_message_reactions')) {
            Schema::create('proximity_chatroom_message_reactions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('proximity_chatroom_message_id');
                $table->foreign('proximity_chatroom_message_id', 'pcm_reactions_msg_id_fk')
                      ->references('id')
                      ->on('proximity_chatroom_messages')
                      ->onDelete('cascade');
                
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('emoji');
                $table->timestamps();
                
                $table->unique(['proximity_chatroom_message_id', 'user_id', 'emoji'], 'pcm_reactions_unique');
            });
        }

        if (!Schema::hasTable('proximity_chatroom_message_mentions')) {
            Schema::create('proximity_chatroom_message_mentions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('proximity_chatroom_message_id');
                $table->foreign('proximity_chatroom_message_id', 'pcm_mentions_msg_id_fk')
                      ->references('id')
                      ->on('proximity_chatroom_messages')
                      ->onDelete('cascade');
                
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->integer('offset')->default(0);
                $table->integer('length')->default(0);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proximity_chatroom_message_mentions');
        Schema::dropIfExists('proximity_chatroom_message_reactions');
        Schema::dropIfExists('proximity_chatroom_messages');
        Schema::dropIfExists('proximity_chatroom_members');
        Schema::dropIfExists('proximity_chatrooms');
    }
};
