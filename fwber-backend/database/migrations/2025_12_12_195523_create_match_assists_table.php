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
        Schema::create('match_assists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('matchmaker_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('users')->onDelete('cascade'); // The profile being shared
            $table->foreignId('target_id')->constrained('users')->onDelete('cascade'); // The user viewing the profile
            $table->string('status')->default('viewed'); // viewed, liked, matched
            $table->timestamps();

            $table->unique(['matchmaker_id', 'subject_id', 'target_id']);
            $table->index(['subject_id', 'target_id']); // For fast lookup during matching
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('match_assists');
    }
};
