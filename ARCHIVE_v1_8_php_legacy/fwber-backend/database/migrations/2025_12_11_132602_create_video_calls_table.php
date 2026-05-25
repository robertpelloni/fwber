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
        Schema::create('video_calls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caller_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('receiver_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->enum('status', ['initiated', 'connected', 'missed', 'rejected', 'ended'])->default('initiated');
            $table->integer('duration')->nullable()->comment('Duration in seconds');
            $table->timestamps();

            $table->index(['caller_id', 'created_at']);
            $table->index(['receiver_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_calls');
    }
};
