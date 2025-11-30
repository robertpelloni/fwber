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
        Schema::create('event_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->foreignId('attendee1_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('attendee2_id')->constrained('users')->onDelete('cascade');
            $table->decimal('compatibility_score', 5, 2); // 0.00 to 100.00
            $table->json('match_reasons')->nullable(); // why they matched
            $table->json('shared_interests')->nullable();
            $table->enum('status', ['pending', 'mutual_interest', 'declined', 'met']);
            $table->timestamp('attendee1_responded_at')->nullable();
            $table->timestamp('attendee2_responded_at')->nullable();
            $table->timestamp('first_message_at')->nullable();
            $table->timestamp('met_at_event_at')->nullable();
            $table->integer('chat_message_count')->default(0);
            $table->timestamps();
            
            $table->unique(['event_id', 'attendee1_id', 'attendee2_id']);
            $table->index(['event_id', 'status']);
            $table->index(['compatibility_score']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_matches');
    }
};
