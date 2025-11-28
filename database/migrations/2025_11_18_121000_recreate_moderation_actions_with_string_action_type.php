<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // SQLite-friendly approach: recreate the table with desired schema
        Schema::dropIfExists('moderation_actions');

        Schema::create('moderation_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('moderator_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('target_user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('target_artifact_id')->nullable()->constrained('proximity_artifacts')->onDelete('cascade');
            // Use string to avoid brittle enum restrictions; tests expect values like
            // artifact_removed, flag_dismissed, spoof_confirmed, throttle_removed, etc.
            $table->string('action_type', 64);
            $table->text('reason');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['moderator_id', 'created_at']);
            $table->index(['target_user_id', 'action_type']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('moderation_actions');
    }
};
