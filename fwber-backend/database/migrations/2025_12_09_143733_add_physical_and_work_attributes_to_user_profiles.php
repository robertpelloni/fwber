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
            if (!Schema::hasColumn('user_profiles', 'height_cm')) {
                $table->integer('height_cm')->nullable();
            }
            if (!Schema::hasColumn('user_profiles', 'body_type')) {
                $table->string('body_type')->nullable();
            }
            if (!Schema::hasColumn('user_profiles', 'ethnicity')) {
                $table->string('ethnicity')->nullable();
            }
            if (!Schema::hasColumn('user_profiles', 'occupation')) {
                $table->string('occupation')->nullable();
            }
            if (!Schema::hasColumn('user_profiles', 'education')) {
                $table->string('education')->nullable();
            }
            if (!Schema::hasColumn('user_profiles', 'relationship_status')) {
                $table->string('relationship_status')->nullable();
            }
            if (!Schema::hasColumn('user_profiles', 'looking_for')) {
                $table->json('looking_for')->nullable();
            }
            if (!Schema::hasColumn('user_profiles', 'interested_in')) {
                $table->json('interested_in')->nullable();
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
                'height_cm',
                'body_type',
                'ethnicity',
                'occupation',
                'education',
                'relationship_status',
                'looking_for',
                'interested_in',
            ]);
        });
    }
};
