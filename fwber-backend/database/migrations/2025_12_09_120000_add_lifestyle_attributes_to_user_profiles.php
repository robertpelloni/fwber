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
            $table->string('smoking_status')->nullable(); // yes, no, social
            $table->string('drinking_status')->nullable(); // yes, no, social
            $table->string('cannabis_status')->nullable(); // yes, no, social
            $table->string('dietary_preferences')->nullable(); // vegan, vegetarian, omnivore, etc.
            $table->string('zodiac_sign')->nullable();
            $table->string('relationship_goals')->nullable(); // long-term, short-term, friends, hookup
            $table->boolean('has_children')->default(false);
            $table->boolean('wants_children')->default(false);
            $table->boolean('has_pets')->default(false);
            $table->json('languages')->nullable();
            $table->json('interests')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'smoking_status',
                'drinking_status',
                'cannabis_status',
                'dietary_preferences',
                'zodiac_sign',
                'relationship_goals',
                'has_children',
                'wants_children',
                'has_pets',
                'languages',
                'interests'
            ]);
        });
    }
};
