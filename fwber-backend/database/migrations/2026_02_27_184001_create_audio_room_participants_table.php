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
        Schema::create('audio_room_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audio_room_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('role', ['speaker', 'listener'])->default('listener');
            $table->boolean('is_muted')->default(true);
            $table->timestamps();

            $table->unique(['audio_room_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audio_room_participants');
    }
};
