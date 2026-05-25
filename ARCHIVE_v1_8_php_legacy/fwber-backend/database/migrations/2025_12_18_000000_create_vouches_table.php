<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vouches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('to_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('from_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type'); // safe, fun, hot
            $table->string('ip_address')->nullable();
            $table->timestamps();

            $table->index(['to_user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vouches');
    }
};
