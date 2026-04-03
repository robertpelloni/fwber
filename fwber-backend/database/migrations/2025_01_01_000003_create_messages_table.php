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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('receiver_id')->constrained('users')->onDelete('cascade');
            $table->text('body');
            $table->string('type')->default('text'); // text, audio, image
            $table->boolean('is_encrypted')->default(false);
            $table->timestamp('read_at')->nullable();
            
            // Audio support
            $table->text('transcription')->nullable();
            $table->integer('duration_seconds')->nullable();
            
            // Moderation
            $table->boolean('is_flagged')->default(false);
            $table->string('flag_reason')->nullable();

            $table->timestamps();
            $table->index(['sender_id', 'receiver_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
