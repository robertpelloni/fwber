<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_moderation_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->onDelete('cascade');
            $table->unsignedBigInteger('actor_user_id');
            $table->unsignedBigInteger('target_user_id');
            $table->string('action', 50); // ban, unban, mute, unmute, kick, role_change, ownership_transfer
            $table->string('reason', 255)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at')->useCurrent();
            $table->timestamps();
            $table->index(['group_id', 'occurred_at']);
            $table->index('actor_user_id');
            $table->index('target_user_id');
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_moderation_events');
    }
};
