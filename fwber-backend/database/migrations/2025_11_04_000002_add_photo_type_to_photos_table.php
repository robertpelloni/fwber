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
        Schema::table('photos', function (Blueprint $table) {
            $table->string('photo_type')->default('real')->after('is_primary'); // 'ai' or 'real'
            $table->index(['user_id', 'photo_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'photo_type']);
            $table->dropColumn('photo_type');
        });
    }
};
