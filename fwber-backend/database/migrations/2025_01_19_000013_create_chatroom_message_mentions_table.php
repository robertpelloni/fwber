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
        Schema::create('chatroom_message_mentions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chatroom_message_id');
            $table->unsignedBigInteger('mentioned_user_id');
            $table->unsignedInteger('position');
            $table->unsignedInteger('length');
            $table->timestamps();

            $table->foreign('chatroom_message_id')->references('id')->on('chatroom_messages')->onDelete('cascade');
            $table->foreign('mentioned_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['chatroom_message_id', 'mentioned_user_id'], 'cmm_message_user_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chatroom_message_mentions');
    }
};
