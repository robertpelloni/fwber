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
        Schema::create('chatroom_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chatroom_id');
            $table->unsignedBigInteger('user_id');
            $table->enum('role', ['member', 'moderator', 'admin'])->default('member');
            $table->boolean('is_muted')->default(false);
            $table->boolean('is_banned')->default(false);
            $table->timestamp('joined_at');
            $table->timestamp('last_seen_at')->nullable();
            $table->json('preferences')->nullable(); // User preferences for this chatroom
            $table->timestamps();
            
            $table->foreign('chatroom_id')->references('id')->on('chatrooms')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['chatroom_id', 'user_id']);
            $table->index(['user_id', 'joined_at']);
            $table->index(['chatroom_id', 'role']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chatroom_members');
    }
};
