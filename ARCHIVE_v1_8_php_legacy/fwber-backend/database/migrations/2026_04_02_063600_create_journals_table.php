<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('journals')) {
            Schema::create('journals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('title', 120)->nullable();
                $table->text('content');
                $table->string('visibility', 20)->default('friends');
                $table->foreignId('circle_group_id')->nullable()->constrained('groups')->nullOnDelete();
                $table->json('tags')->nullable();
                $table->string('mood_emoji', 10)->nullable();
                $table->string('accent_color', 20)->nullable();
                $table->timestamps();

                $table->index(['user_id', 'visibility']);
                $table->index('created_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('journals');
    }
};
