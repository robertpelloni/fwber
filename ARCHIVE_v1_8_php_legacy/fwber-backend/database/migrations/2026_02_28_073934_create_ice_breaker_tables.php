<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ice_breaker_questions', function (Blueprint $table) {
            $table->id();
            $table->string('question');
            $table->string('category'); // fun, deep, creative, spicy
            $table->string('emoji', 10)->default('❓');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('ice_breaker_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('question_id')->constrained('ice_breaker_questions')->onDelete('cascade');
            $table->unsignedBigInteger('match_user_id');
            $table->text('answer');
            $table->timestamps();

            $table->foreign('match_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['user_id', 'question_id', 'match_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ice_breaker_answers');
        Schema::dropIfExists('ice_breaker_questions');
    }
};
