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
        Schema::create('venue_checkins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('venue_id')->constrained()->onDelete('cascade');
            $table->text('message')->nullable();
            $table->timestamp('checked_out_at')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['venue_id', 'checked_out_at']); // To find currently active users in a venue
            $table->index(['user_id', 'created_at']); // To show user's check-in history
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('venue_checkins');
    }
};
