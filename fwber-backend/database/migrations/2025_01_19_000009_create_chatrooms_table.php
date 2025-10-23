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
        Schema::create('chatrooms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['interest', 'city', 'event', 'private'])->default('interest');
            $table->string('category')->nullable(); // e.g., 'music', 'fitness', 'food'
            $table->string('city')->nullable(); // e.g., 'New York', 'Los Angeles'
            $table->string('neighborhood')->nullable(); // e.g., 'Downtown', 'Campus'
            $table->unsignedBigInteger('created_by');
            $table->boolean('is_public')->default(true);
            $table->boolean('is_active')->default(true);
            $table->integer('member_count')->default(0);
            $table->integer('message_count')->default(0);
            $table->timestamp('last_activity_at')->nullable();
            $table->json('settings')->nullable(); // Custom settings for the chatroom
            $table->timestamps();
            
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->index(['type', 'category']);
            $table->index(['type', 'city']);
            $table->index(['is_active', 'last_activity_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chatrooms');
    }
};
