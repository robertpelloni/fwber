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
        Schema::create('proximity_chatroom_members', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('proximity_chatroom_id')->unsigned();
            $table->bigInteger('user_id')->unsigned();
            $table->enum('role', ['admin', 'moderator', 'member'])->default('member');
            $table->boolean('is_muted')->default(false);
            $table->boolean('is_banned')->default(false);
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->integer('distance_meters')->nullable(); // Distance from chatroom center
            $table->timestamp('joined_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('last_seen_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('last_location_update')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->json('preferences')->nullable();
            $table->json('professional_info')->nullable(); // Job title, company, industry, etc.
            $table->json('interests')->nullable(); // Professional and personal interests
            $table->boolean('is_visible')->default(true); // Whether user wants to be discoverable
            $table->boolean('is_networking')->default(false); // Looking for professional connections
            $table->boolean('is_social')->default(true); // Looking for social connections
            $table->timestamps();

            $table->foreign('proximity_chatroom_id')->references('id')->on('proximity_chatrooms')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['proximity_chatroom_id', 'user_id']);
            $table->index(['proximity_chatroom_id', 'is_visible'], 'prox_chat_members_chatroom_id_visible_idx');
            $table->index(['proximity_chatroom_id', 'is_networking'], 'prox_chat_members_chatroom_id_networking_idx');
            $table->index(['proximity_chatroom_id', 'is_social'], 'prox_chat_members_chatroom_id_social_idx');
            $table->index('last_seen_at');
            $table->index('last_location_update');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proximity_chatroom_members');
    }
};
