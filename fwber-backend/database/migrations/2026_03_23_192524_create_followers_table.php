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
        Schema::create('followers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade')->comment('The local user being followed');
            $table->string('actor_uri')->index()->comment('The ActivityPub URI of the remote follower');
            $table->string('username')->nullable();
            $table->string('domain')->nullable();
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('accepted');
            $table->timestamps();

            $table->unique(['user_id', 'actor_uri']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('followers');
    }
};
