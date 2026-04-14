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
        Schema::create('group_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id_1')->constrained('groups')->cascadeOnDelete();
            $table->foreignId('group_id_2')->constrained('groups')->cascadeOnDelete();
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
            $table->foreignId('initiated_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            // Ensure unique pairing regardless of order
            $table->unique(['group_id_1', 'group_id_2']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_matches');
    }
};
