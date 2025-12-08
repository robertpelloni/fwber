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
        if (!Schema::hasTable('match_actions')) {
            Schema::create('match_actions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('target_user_id')->constrained('users')->onDelete('cascade');
                $table->string('action'); // like, pass, super_like
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('device_tokens')) {
            Schema::create('device_tokens', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->string('token');
                $table->string('type'); // fcm, apns
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('group_moderation_events')) {
            Schema::create('group_moderation_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('group_id')->constrained('groups')->onDelete('cascade');
                $table->foreignId('actor_user_id')->constrained('users');
                $table->foreignId('target_user_id')->constrained('users');
                $table->string('action');
                $table->string('reason')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('occurred_at')->useCurrent();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('user_locations')) {
            Schema::create('user_locations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->decimal('latitude', 10, 8);
                $table->decimal('longitude', 11, 8);
                $table->decimal('accuracy', 8, 2)->nullable();
                $table->decimal('heading', 5, 2)->nullable();
                $table->decimal('speed', 5, 2)->nullable();
                $table->decimal('altitude', 8, 2)->nullable();
                $table->boolean('is_active')->default(true);
                $table->string('privacy_level')->default('private');
                $table->timestamp('last_updated')->useCurrent();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('user_physical_profiles')) {
            Schema::create('user_physical_profiles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->integer('height_cm')->nullable();
                $table->string('body_type')->nullable();
                $table->string('hair_color')->nullable();
                $table->string('eye_color')->nullable();
                $table->string('skin_tone')->nullable();
                $table->string('ethnicity')->nullable();
                $table->string('facial_hair')->nullable();
                $table->string('tattoos')->nullable();
                $table->string('piercings')->nullable();
                $table->string('dominant_hand')->nullable();
                $table->string('fitness_level')->nullable();
                $table->string('clothing_style')->nullable();
                $table->text('avatar_prompt')->nullable();
                $table->string('avatar_image_url')->nullable();
                $table->string('avatar_status')->default('pending');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_physical_profiles');
        Schema::dropIfExists('user_locations');
        Schema::dropIfExists('group_moderation_events');
        Schema::dropIfExists('device_tokens');
        Schema::dropIfExists('match_actions');
    }
};
