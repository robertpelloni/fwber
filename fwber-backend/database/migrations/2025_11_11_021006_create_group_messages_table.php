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
        Schema::create('group_messages', function (Blueprint $table) {
            $table->id();
                $table->foreignId('group_id')->constrained('groups')->onDelete('cascade');
                $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
                $table->text('content')->nullable();
                $table->enum('message_type', ['text', 'image', 'video', 'audio', 'file'])->default('text');
                $table->string('media_url')->nullable();
                $table->string('media_type')->nullable();
                $table->integer('media_duration')->nullable();
                $table->string('thumbnail_url')->nullable();
                $table->timestamp('sent_at')->useCurrent();
                $table->boolean('is_deleted')->default(false);
            $table->timestamps();
            
                $table->index(['group_id', 'sent_at']);
                $table->index('sender_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_messages');
    }
};
