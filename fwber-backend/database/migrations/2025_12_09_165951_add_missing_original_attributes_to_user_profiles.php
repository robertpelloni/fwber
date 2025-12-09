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
            if (!Schema::hasColumn('user_profiles', 'pronouns')) {
                $table->string('pronouns')->nullable()->after('gender');
            }
            if (!Schema::hasColumn('user_profiles', 'sexual_orientation')) {
                $table->string('sexual_orientation')->nullable()->after('pronouns');
            }
            if (!Schema::hasColumn('user_profiles', 'relationship_style')) {
                $table->string('relationship_style')->nullable()->after('relationship_status');
            }
            if (!Schema::hasColumn('user_profiles', 'hair_color')) {
                $table->string('hair_color')->nullable()->after('body_type');
            }
            if (!Schema::hasColumn('user_profiles', 'eye_color')) {
                $table->string('eye_color')->nullable()->after('hair_color');
            }
            if (!Schema::hasColumn('user_profiles', 'skin_tone')) {
                $table->string('skin_tone')->nullable()->after('eye_color');
            }
            if (!Schema::hasColumn('user_profiles', 'facial_hair')) {
                $table->string('facial_hair')->nullable()->after('skin_tone');
            }
            if (!Schema::hasColumn('user_profiles', 'dominant_hand')) {
                $table->string('dominant_hand')->nullable()->after('facial_hair');
            }
            if (!Schema::hasColumn('user_profiles', 'fitness_level')) {
                $table->string('fitness_level')->nullable()->after('dominant_hand');
            }
            if (!Schema::hasColumn('user_profiles', 'clothing_style')) {
                $table->string('clothing_style')->nullable()->after('fitness_level');
            }
            if (!Schema::hasColumn('user_profiles', 'avatar_prompt')) {
                $table->text('avatar_prompt')->nullable();
            }
            if (!Schema::hasColumn('user_profiles', 'avatar_status')) {
                $table->string('avatar_status')->default('pending');
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
                'pronouns',
                'sexual_orientation',
                'relationship_style',
                'hair_color',
                'eye_color',
                'skin_tone',
                'facial_hair',
                'dominant_hand',
                'fitness_level',
                'clothing_style',
                'avatar_prompt',
                'avatar_status'
            ]);
        });
    }
};
