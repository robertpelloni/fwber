<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Stage 3 Nuclear Schema Recovery.
     * 
     * This migration ensures that all major product tables that are 'ghosted' 
     * (present in migration ledger but absent in DB) are forcefully recovered.
     */
    public function up(): void
    {
        // --- 1. USER TABLE RECOVERY ---
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'current_streak')) {
                $table->integer('current_streak')->default(0)->after('token_balance');
            }
            if (! Schema::hasColumn('users', 'last_daily_bonus_at')) {
                $table->timestamp('last_daily_bonus_at')->nullable()->after('current_streak');
            }
        });

        // --- 2. TOKEN & REWARDS ---
        if (! Schema::hasTable('token_transactions')) {
            Schema::create('token_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->decimal('amount', 12, 4);
                $table->string('type');
                $table->string('description')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->index('user_id');
            });
        }

        if (! Schema::hasTable('achievements')) {
            Schema::create('achievements', function (Blueprint $table) {
                $table->id();
                $table->string('slug')->unique();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('icon')->nullable();
                $table->integer('token_reward')->default(0);
                $table->json('criteria')->nullable();
                $table->timestamps();
            });
            DB::table('achievements')->insertOrIgnore([
                ['slug' => 'profile_verified', 'name' => 'Verified Human', 'token_reward' => 50, 'created_at' => now(), 'updated_at' => now()],
                ['slug' => 'first_match', 'name' => 'First Connection', 'token_reward' => 10, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        if (! Schema::hasTable('user_achievements')) {
            Schema::create('user_achievements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('achievement_id')->constrained()->onDelete('cascade');
                $table->timestamp('unlocked_at')->useCurrent();
                $table->timestamps();
                $table->unique(['user_id', 'achievement_id']);
            });
        }

        // --- 3. GROUPS & COMMUNITIES ---
        if (! Schema::hasTable('groups')) {
            Schema::create('groups', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('icon')->nullable();
                $table->enum('privacy', ['public', 'private'])->default('public');
                $table->enum('visibility', ['visible', 'hidden'])->default('visible');
                $table->foreignId('created_by_user_id')->nullable()->constrained('users');
                $table->integer('member_count')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->index('created_by_user_id');
            });
        }

        if (! Schema::hasTable('group_members')) {
            Schema::create('group_members', function (Blueprint $table) {
                $table->id();
                $table->foreignId('group_id')->constrained()->onDelete('cascade');
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('role')->default('member');
                $table->boolean('is_banned')->default(false);
                $table->timestamp('joined_at')->useCurrent();
                $table->timestamps();
                $table->unique(['group_id', 'user_id']);
            });
        }

        if (! Schema::hasTable('group_posts')) {
            Schema::create('group_posts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('group_id')->constrained()->onDelete('cascade');
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->text('content');
                $table->timestamps();
            });
        }

        // --- 4. CHATROOMS ---
        if (! Schema::hasTable('chatrooms')) {
            Schema::create('chatrooms', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100);
                $table->string('description', 500)->nullable();
                $table->enum('type', ['interest', 'city', 'event', 'private', 'group']);
                $table->string('category', 50)->nullable();
                $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
                $table->boolean('is_public')->default(true);
                $table->boolean('is_active')->default(true);
                $table->integer('member_count')->default(0);
                $table->integer('message_count')->default(0);
                $table->timestamp('last_activity_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('chatroom_members')) {
            Schema::create('chatroom_members', function (Blueprint $table) {
                $table->id();
                $table->foreignId('chatroom_id')->constrained()->onDelete('cascade');
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('role')->default('member');
                $table->boolean('is_muted')->default(false);
                $table->timestamp('joined_at')->useCurrent();
                $table->timestamps();
                $table->unique(['chatroom_id', 'user_id']);
            });
        }

        if (! Schema::hasTable('chatroom_messages')) {
            Schema::create('chatroom_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('chatroom_id')->constrained()->onDelete('cascade');
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->text('content');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('chatroom_message_reactions')) {
            Schema::create('chatroom_message_reactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('chatroom_message_id')->constrained()->onDelete('cascade');
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('emoji');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('chatroom_message_mentions')) {
            Schema::create('chatroom_message_mentions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('chatroom_message_id')->constrained()->onDelete('cascade');
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->timestamps();
            });
        }

        // --- 5. PROXIMITY CHATROOMS ---
        if (! Schema::hasTable('proximity_chatrooms')) {
            Schema::create('proximity_chatrooms', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->decimal('lat', 10, 7);
                $table->decimal('lng', 10, 7);
                $table->unsignedInteger('radius_m')->default(500);
                $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('proximity_chatroom_members')) {
            Schema::create('proximity_chatroom_members', function (Blueprint $table) {
                $table->id();
                $table->foreignId('proximity_chatroom_id')->constrained('proximity_chatrooms')->onDelete('cascade');
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->timestamp('joined_at')->useCurrent();
                $table->timestamps();
                $table->unique(['proximity_chatroom_id', 'user_id'], 'prox_room_user_unique');
            });
        }

        if (! Schema::hasTable('proximity_chatroom_messages')) {
            Schema::create('proximity_chatroom_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('proximity_chatroom_id')->constrained('proximity_chatrooms')->onDelete('cascade');
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->text('content');
                $table->timestamps();
            });
        }

        // --- 6. PROXIMITY ARTIFACTS & LOCAL LOOP ---
        if (! Schema::hasTable('proximity_artifacts')) {
            Schema::create('proximity_artifacts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('type')->index(); // chat, board_post, announce, token_drop, promotion
                $table->text('content');
                $table->decimal('location_lat', 10, 7)->index();
                $table->decimal('location_lng', 10, 7)->index();
                $table->unsignedInteger('visibility_radius_m')->default(1000);
                $table->string('moderation_status')->default('clean')->index();
                $table->json('meta')->nullable();
                $table->timestamp('expires_at')->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('proximity_artifact_comments')) {
            Schema::create('proximity_artifact_comments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('proximity_artifact_id')->constrained()->onDelete('cascade');
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->text('content');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('proximity_artifact_votes')) {
            Schema::create('proximity_artifact_votes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('proximity_artifact_id')->constrained()->onDelete('cascade');
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->integer('value')->default(1);
                $table->timestamps();
                $table->unique(['proximity_artifact_id', 'user_id']);
            });
        }

        // --- 7. TOPICS & ENGAGEMENT ---
        if (! Schema::hasTable('topics')) {
            Schema::create('topics', function (Blueprint $table) {
                $table->id();
                $table->string('slug')->unique();
                $table->string('label');
                $table->string('emoji')->nullable();
                $table->string('category')->nullable();
                $table->text('description')->nullable();
                $table->integer('follower_count')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('audio_rooms')) {
            Schema::create('audio_rooms', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('audio_room_participants')) {
            Schema::create('audio_room_participants', function (Blueprint $table) {
                $table->id();
                $table->foreignId('audio_room_id')->constrained()->onDelete('cascade');
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('role')->default('listener');
                $table->timestamp('joined_at')->useCurrent();
                $table->timestamps();
            });
        }

        // --- 8. MISC GHOSTED TABLES ---
        if (! Schema::hasTable('friend_requests')) {
            Schema::create('friend_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('friend_id')->constrained('users')->onDelete('cascade');
                $table->string('status')->default('pending');
                $table->timestamps();
                $table->unique(['user_id', 'friend_id']);
            });
        }

        // --- 9. ACTIVITY REPAIR (Ensuring common columns exist) ---
        if (Schema::hasTable('user_profiles')) {
            Schema::table('user_profiles', function (Blueprint $table) {
                if (! Schema::hasColumn('user_profiles', 'looking_for')) {
                    $table->json('looking_for')->nullable()->after('relationship_style');
                }
                if (! Schema::hasColumn('user_profiles', 'interests')) {
                    $table->json('interests')->nullable()->after('languages');
                }
            });
        }
    }

    public function down(): void
    {
        // Recovery migration down path is a no-op.
    }
};
