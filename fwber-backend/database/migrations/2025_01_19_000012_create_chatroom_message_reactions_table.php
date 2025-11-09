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
        Schema::create('chatroom_message_reactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chatroom_message_id');
            $table->unsignedBigInteger('user_id');
            $table->string('emoji', 32);
            $table->timestamps();

            $table->foreign('chatroom_message_id')->references('id')->on('chatroom_messages')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['chatroom_message_id', 'emoji']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chatroom_message_reactions');
    }
};
