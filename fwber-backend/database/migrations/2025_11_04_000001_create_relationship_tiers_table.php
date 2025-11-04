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
        Schema::create('relationship_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('matches')->onDelete('cascade');
            $table->string('current_tier')->default('discovery'); // discovery, matched, connected, established, verified
            $table->integer('messages_exchanged')->default(0);
            $table->integer('days_connected')->default(0);
            $table->boolean('has_met_in_person')->default(false);
            $table->timestamp('first_matched_at')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamp('met_in_person_at')->nullable();
            $table->timestamps();

            // Ensure one tier record per match
            $table->unique('match_id');
            
            // Index for queries
            $table->index('current_tier');
            $table->index(['match_id', 'current_tier']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('relationship_tiers');
    }
};
