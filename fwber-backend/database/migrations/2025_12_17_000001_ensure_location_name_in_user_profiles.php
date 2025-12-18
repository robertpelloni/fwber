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
            // Ensure latitude exists
            if (!Schema::hasColumn('user_profiles', 'latitude')) {
                $table->decimal('latitude', 10, 8)->nullable();
            }

            // Ensure longitude exists
            if (!Schema::hasColumn('user_profiles', 'longitude')) {
                $table->decimal('longitude', 11, 8)->nullable();
            }

            // Ensure location_name exists
            if (!Schema::hasColumn('user_profiles', 'location_name')) {
                if (Schema::hasColumn('user_profiles', 'longitude')) {
                    $table->string('location_name')->nullable()->after('longitude');
                } else {
                    $table->string('location_name')->nullable();
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('user_profiles', 'location_name')) {
                $table->dropColumn('location_name');
            }
            // We don't drop latitude/longitude here as they might have existed before
        });
    }
};
