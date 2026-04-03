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
        Schema::create('user_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user1_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('user2_id')->constrained('users')->onDelete('cascade');
            $table->integer('match_score')->default(0);
            $table->boolean('nfc_verified')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['user1_id', 'user2_id']);
        });

        Schema::create('match_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('target_user_id')->constrained('users')->onDelete('cascade');
            $table->enum('action', ['like', 'pass']);
            $table->timestamps();

            $table->unique(['user_id', 'target_user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('match_actions');
        Schema::dropIfExists('user_matches');
    }
};
