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
        Schema::create('bulletin_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bulletin_board_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('content'); // Message content
            $table->json('metadata')->nullable(); // Additional metadata (reactions, etc.)
            $table->boolean('is_anonymous')->default(false); // Anonymous posting option
            $table->boolean('is_moderated')->default(false); // Moderation status
            $table->timestamp('expires_at')->nullable(); // Optional message expiration
            $table->integer('reaction_count')->default(0); // Cached reaction count
            $table->integer('reply_count')->default(0); // Cached reply count
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['bulletin_board_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index('expires_at');
            $table->index('is_moderated');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bulletin_messages');
    }
};
