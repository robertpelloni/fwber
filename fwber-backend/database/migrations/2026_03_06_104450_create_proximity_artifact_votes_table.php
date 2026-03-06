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
        Schema::dropIfExists('proximity_artifact_votes');
        Schema::create('proximity_artifact_votes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('proximity_artifact_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('value'); // 1 = upvote, -1 = downvote
            $table->timestamps();
            
            // A user can only have one active vote per artifact
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
