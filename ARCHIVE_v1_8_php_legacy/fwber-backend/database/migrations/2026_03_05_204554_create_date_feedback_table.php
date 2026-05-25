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
        Schema::create('date_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('matches')->cascadeOnDelete();
            $table->foreignId('reporting_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('subject_user_id')->constrained('users')->cascadeOnDelete();
            $table->tinyInteger('rating')->comment('1-5 stars');
            $table->text('feedback_text')->nullable();
            $table->boolean('safety_concerns')->default(false);
            $table->timestamps();

            // A user can only rate a specific match once
            $table->unique(['match_id', 'reporting_user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('date_feedback');
    }
};
