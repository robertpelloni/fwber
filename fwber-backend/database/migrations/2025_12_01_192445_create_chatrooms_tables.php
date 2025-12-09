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
        if (!Schema::hasTable('chatrooms')) {
            Schema::create('chatrooms', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100);
                $table->string('description', 500)->nullable();
                $table->enum('type', ['interest', 'city', 'event', 'private']);
                $table->string('category', 50)->nullable();
                $table->string('city', 100)->nullable();
                $table->string('neighborhood', 100)->nullable();
                $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
                $table->boolean('is_public')->default(true);
                $table->boolean('is_active')->default(true);
                $table->integer('member_count')->default(0);
                $table->integer('message_count')->default(0);
                $table->timestamp('last_activity_at')->nullable();
                $table->json('settings')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('chatroom_members')) {
            Schema::create('chatroom_members', function (Blueprint $table) {
                $table->id();
                $table->foreignId('chatroom_id')->constrained()->onDelete('cascade');
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('role')->default('member');
                $table->boolean('is_muted')->default(false);
                $table->boolean('is_banned')->default(false);
                $table->timestamp('joined_at');
                $table->timestamp('last_seen_at')->nullable();
                $table->json('preferences')->nullable();
                $table->timestamps();

                $table->unique(['chatroom_id', 'user_id']);
            });
        }

        if (!Schema::hasTable('chatroom_messages')) {
            Schema::create('chatroom_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('chatroom_id')->constrained()->onDelete('cascade');
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('parent_id')->nullable()->constrained('chatroom_messages')->onDelete('cascade');
                $table->text('content');
                $table->string('type')->default('text');
                $table->json('metadata')->nullable();
                $table->boolean('is_edited')->default(false);
                $table->boolean('is_deleted')->default(false);
                $table->boolean('is_pinned')->default(false);
                $table->boolean('is_announcement')->default(false);
                $table->integer('reaction_count')->default(0);
                $table->integer('reply_count')->default(0);
                $table->timestamp('edited_at')->nullable();
                $table->timestamp('deleted_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('chatroom_message_reactions')) {
            Schema::create('chatroom_message_reactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('chatroom_message_id')->constrained()->onDelete('cascade');
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('emoji');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('chatroom_message_mentions')) {
            Schema::create('chatroom_message_mentions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('chatroom_message_id')->constrained()->onDelete('cascade');
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chatroom_message_mentions');
        Schema::dropIfExists('chatroom_message_reactions');
        Schema::dropIfExists('chatroom_messages');
        Schema::dropIfExists('chatroom_members');
        Schema::dropIfExists('chatrooms');
    }
};
