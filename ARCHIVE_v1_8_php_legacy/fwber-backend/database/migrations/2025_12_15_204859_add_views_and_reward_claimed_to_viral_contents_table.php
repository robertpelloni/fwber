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
        Schema::table('viral_contents', function (Blueprint $table) {
            $table->unsignedInteger('views')->default(0);
            $table->boolean('reward_claimed')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('viral_contents', function (Blueprint $table) {
            $table->dropColumn(['views', 'reward_claimed']);
        });
    }
};
