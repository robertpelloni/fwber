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
            // Basic Info
            if (!Schema::hasColumn('user_profiles', 'display_name')) {
                $table->string('display_name')->nullable();
            }
            if (!Schema::hasColumn('user_profiles', 'bio')) {
                $table->text('bio')->nullable();
            }
            if (!Schema::hasColumn('user_profiles', 'birthdate')) {
                $table->date('birthdate')->nullable();
            }
            if (!Schema::hasColumn('user_profiles', 'gender')) {
                $table->string('gender')->nullable();
            }

            // Location
            if (!Schema::hasColumn('user_profiles', 'latitude')) {
                $table->decimal('latitude', 10, 8)->nullable();
            }
            if (!Schema::hasColumn('user_profiles', 'longitude')) {
                $table->decimal('longitude', 11, 8)->nullable();
            }
            if (!Schema::hasColumn('user_profiles', 'location_name')) {
                $table->string('location_name')->nullable();
            }

            // Preferences & JSON fields
            if (!Schema::hasColumn('user_profiles', 'looking_for')) {
                $table->json('looking_for')->nullable();
            }
            if (!Schema::hasColumn('user_profiles', 'preferences')) {
                $table->json('preferences')->nullable();
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
        // We don't drop columns in down() to avoid data loss if they existed before
    }
};
