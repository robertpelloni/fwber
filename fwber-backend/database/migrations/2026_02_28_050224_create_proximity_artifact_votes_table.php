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
        Schema::create('proximity_artifact_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proximity_artifact_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('vote'); // 1 for upvote, -1 for downvote
            $table->timestamps();
            
            // A user can only vote once per artifact
            $table->unique(['proximity_artifact_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proximity_artifact_votes');
    }
};
