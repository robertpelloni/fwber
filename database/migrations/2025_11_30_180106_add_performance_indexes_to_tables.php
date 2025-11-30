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
        // Optimize User Profiles for Geospatial and Demographic queries
        Schema::table('user_profiles', function (Blueprint $table) {
            // Composite index for bounding box queries
            $table->index(['latitude', 'longitude'], 'profiles_geo_index');
            
            // Indexes for common filters
            $table->index('gender');
            $table->index('birthdate');
            
            // Composite index for "nearby users of specific gender" (common match query)
            $table->index(['latitude', 'longitude', 'gender'], 'profiles_geo_gender_index');
        });

        // Optimize Proximity Artifacts for Feed queries
        if (Schema::hasTable('proximity_artifacts')) {
            Schema::table('proximity_artifacts', function (Blueprint $table) {
                // Covering index for the main feed query: active, clean, within box
                // Note: We can't easily index 'expires_at' > NOW() in a composite index effectively with lat/lng ranges,
                // but having them indexed helps the query optimizer.
                // The existing migration had individual indexes on lat/lng.
                // We add a composite one for better bounding box performance combined with status.
                $table->index(['location_lat', 'location_lng', 'moderation_status', 'expires_at'], 'artifacts_geo_active_index');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropIndex('profiles_geo_index');
            $table->dropIndex(['gender']);
            $table->dropIndex(['birthdate']);
            $table->dropIndex('profiles_geo_gender_index');
        });

        if (Schema::hasTable('proximity_artifacts')) {
            Schema::table('proximity_artifacts', function (Blueprint $table) {
                $table->dropIndex('artifacts_geo_active_index');
            });
        }
    }
};
