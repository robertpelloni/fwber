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
        Schema::table('user_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('user_profiles', 'breast_size')) {
                $table->string('breast_size')->nullable();
            }
            if (!Schema::hasColumn('user_profiles', 'tattoos')) {
                $table->string('tattoos')->nullable(); // None, Some, Many, Full Sleeve, etc.
            }
            if (!Schema::hasColumn('user_profiles', 'piercings')) {
                $table->string('piercings')->nullable(); // None, Ears, Face, Body, etc.
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropColumn(['breast_size', 'tattoos', 'piercings']);
        });
    }
};
