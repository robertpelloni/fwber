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
        if (!Schema::hasTable('shadow_throttles')) {
            Schema::create('shadow_throttles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('reason'); // spam, flagged_content, geo_spoof, rapid_posting, manual
                $table->integer('severity')->default(1);
                $table->decimal('visibility_reduction', 3, 2)->default(0.50); // 0.00 to 1.00
                $table->timestamp('started_at')->useCurrent();
                $table->timestamp('expires_at')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('moderation_actions')) {
            Schema::create('moderation_actions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('moderator_id')->constrained('users');
                $table->foreignId('target_user_id')->nullable()->constrained('users');
                // target_artifact_id will be added in a later migration or nullable here if artifacts table exists
                // Since ProximityArtifact might not exist yet, we'll make it nullable and add constraint later or just use bigInteger
                $table->unsignedBigInteger('target_artifact_id')->nullable(); 
                $table->string('action_type'); // ban, suspend, warn, delete_content
                $table->string('reason');
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('reports')) {
            Schema::create('reports', function (Blueprint $table) {
                $table->id();
                $table->foreignId('reporter_id')->constrained('users');
                $table->foreignId('accused_id')->constrained('users');
                $table->unsignedBigInteger('message_id')->nullable(); // Polymorphic or specific? Model says message_id
                $table->string('reason');
                $table->text('details')->nullable();
                $table->string('status')->default('pending'); // pending, reviewed, resolved, dismissed
                $table->text('resolution_notes')->nullable();
                $table->foreignId('moderator_id')->nullable()->constrained('users');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('blocks')) {
            Schema::create('blocks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('blocker_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('blocked_id')->constrained('users')->onDelete('cascade');
                $table->timestamps();

                $table->unique(['blocker_id', 'blocked_id']);
            });
        }

        if (!Schema::hasTable('proximity_artifacts')) {
            Schema::create('proximity_artifacts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->string('type'); // note, photo, audio
                $table->text('content')->nullable();
                $table->decimal('location_lat', 10, 8);
                $table->decimal('location_lng', 11, 8);
                $table->float('visibility_radius_m')->default(100);
                $table->string('moderation_status')->default('pending'); // pending, approved, rejected, removed
                $table->json('meta')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->boolean('is_flagged')->default(false);
                $table->integer('flag_count')->default(0);
                $table->softDeletes();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('telemetry_events')) {
            Schema::create('telemetry_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('event');
                $table->json('payload')->nullable();
                $table->timestamp('recorded_at')->useCurrent();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('relationship_tiers')) {
            Schema::create('relationship_tiers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('match_id')->constrained('matches')->onDelete('cascade');
                $table->string('current_tier')->default('discovery');
                $table->integer('messages_exchanged')->default(0);
                $table->integer('days_connected')->default(0);
                $table->boolean('has_met_in_person')->default(false);
                $table->timestamp('first_matched_at')->nullable();
                $table->timestamp('last_message_at')->nullable();
                $table->timestamp('met_in_person_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('friends')) {
            Schema::create('friends', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('friend_id')->constrained('users')->onDelete('cascade');
                $table->string('status')->default('pending'); // pending, accepted, declined
                $table->timestamps();

                $table->unique(['user_id', 'friend_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('friends');
        Schema::dropIfExists('relationship_tiers');
        Schema::dropIfExists('telemetry_events');
        Schema::dropIfExists('proximity_artifacts');
        Schema::dropIfExists('blocks');
        Schema::dropIfExists('reports');
        Schema::dropIfExists('moderation_actions');
        Schema::dropIfExists('shadow_throttles');
    }
};
