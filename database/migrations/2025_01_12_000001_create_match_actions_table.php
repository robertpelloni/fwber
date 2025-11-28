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
        if (!Schema::hasTable('match_actions')) {
            Schema::create('match_actions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('target_user_id')->constrained('users')->onDelete('cascade');
                $table->enum('action', ['like', 'pass', 'super_like']);
                $table->timestamps();
                
                // Prevent duplicate actions
                $table->unique(['user_id', 'target_user_id']);
                
                // Index for performance
                $table->index(['user_id', 'action']);
                $table->index(['target_user_id', 'action']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('match_actions');
    }
};
