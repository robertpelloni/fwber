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
        Schema::create('chatroom_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chatroom_id');
            $table->unsignedBigInteger('user_id');
            $table->text('content');
            $table->enum('type', ['text', 'image', 'file', 'system'])->default('text');
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->string('file_size')->nullable();
            $table->string('file_type')->nullable();
            $table->unsignedBigInteger('reply_to')->nullable(); // For threaded conversations
            $table->boolean('is_edited')->default(false);
            $table->timestamp('edited_at')->nullable();
            $table->boolean('is_deleted')->default(false);
            $table->timestamp('deleted_at')->nullable();
            $table->json('reactions')->nullable(); // User reactions to the message
            $table->json('metadata')->nullable(); // Additional message metadata
            $table->timestamps();
            
            $table->foreign('chatroom_id')->references('id')->on('chatrooms')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('reply_to')->references('id')->on('chatroom_messages')->onDelete('set null');
            $table->index(['chatroom_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['type', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chatroom_messages');
    }
};
