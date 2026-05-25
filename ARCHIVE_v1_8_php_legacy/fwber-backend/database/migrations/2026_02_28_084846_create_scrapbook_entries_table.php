<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scrapbook_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('match_user_id');
            $table->string('type'); // text, image, voice, link
            $table->text('content'); // text body, URL, or caption
            $table->string('media_url')->nullable();
            $table->string('media_type')->nullable(); // image/jpeg, audio/webm, etc.
            $table->string('emoji')->nullable();
            $table->string('color')->nullable(); // note accent color
            $table->boolean('is_pinned')->default(false);
            $table->timestamps();

            $table->foreign('match_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['user_id', 'match_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scrapbook_entries');
    }
};
