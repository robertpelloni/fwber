<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user1_id')->constrained('fwber_users')->onDelete('cascade');
            $table->foreignId('user2_id')->constrained('fwber_users')->onDelete('cascade');
            
            // Match status
            $table->enum('status', ['pending', 'accepted', 'rejected', 'blocked'])->default('pending');
            
            // Match score (compatibility percentage)
            $table->integer('match_score')->default(0);
            
            // Timestamps
            $table->timestamp('matched_at')->useCurrent();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->unique(['user1_id', 'user2_id']);
            $table->index(['user1_id', 'status']);
            $table->index(['user2_id', 'status']);
            $table->index('match_score');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};
