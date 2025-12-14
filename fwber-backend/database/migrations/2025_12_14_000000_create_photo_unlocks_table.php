<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('photo_unlocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('photo_id')->constrained()->onDelete('cascade');
            $table->decimal('cost', 8, 2);
            $table->timestamp('unlocked_at');
            $table->timestamps();

            $table->unique(['user_id', 'photo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('photo_unlocks');
    }
};
