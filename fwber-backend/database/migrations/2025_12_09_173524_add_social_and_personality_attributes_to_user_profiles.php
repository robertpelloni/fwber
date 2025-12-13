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
            if (!Schema::hasColumn('user_profiles', 'love_language')) {
                $table->string('love_language')->nullable()->after('preferences');
            }
            if (!Schema::hasColumn('user_profiles', 'personality_type')) {
                $table->string('personality_type')->nullable()->after('love_language');
            }
            if (!Schema::hasColumn('user_profiles', 'political_views')) {
                $table->string('political_views')->nullable()->after('personality_type');
            }
            if (!Schema::hasColumn('user_profiles', 'religion')) {
                $table->string('religion')->nullable()->after('political_views');
            }
            if (!Schema::hasColumn('user_profiles', 'sleep_schedule')) {
                $table->string('sleep_schedule')->nullable()->after('religion');
            }
            if (!Schema::hasColumn('user_profiles', 'social_media')) {
                $table->json('social_media')->nullable()->after('sleep_schedule');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'love_language',
                'personality_type',
                'political_views',
                'religion',
                'sleep_schedule',
                'social_media'
            ]);
        });
    }
};
