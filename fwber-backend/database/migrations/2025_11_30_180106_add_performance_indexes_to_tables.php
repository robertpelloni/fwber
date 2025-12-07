<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $indexExists = function ($table, $indexName) {
            try {
                return collect(DB::select("SHOW INDEXES FROM " . $table . " WHERE Key_name = ?", [$indexName]))->count() > 0;
            } catch (\Exception $e) {
                return false;
            }
        };

        // Optimize User Profiles for Geospatial and Demographic queries
        
        if (! $indexExists('user_profiles', 'profiles_geo_index')) {
            if (Schema::hasColumn('user_profiles', 'latitude') && Schema::hasColumn('user_profiles', 'longitude')) {
                Schema::table('user_profiles', function (Blueprint $table) {
                    $table->index(['latitude', 'longitude'], 'profiles_geo_index');
                });
            }
        }

        if (! $indexExists('user_profiles', 'user_profiles_gender_index')) {
            if (Schema::hasColumn('user_profiles', 'gender')) {
                Schema::table('user_profiles', function (Blueprint $table) {
                    $table->index('gender');
                });
            }
        }

        if (! $indexExists('user_profiles', 'user_profiles_birthdate_index')) {
            if (Schema::hasColumn('user_profiles', 'birthdate')) {
                Schema::table('user_profiles', function (Blueprint $table) {
                    $table->index('birthdate');
                });
            }
        }

        if (! $indexExists('user_profiles', 'profiles_geo_gender_index')) {
            if (Schema::hasColumn('user_profiles', 'latitude') && Schema::hasColumn('user_profiles', 'longitude') && Schema::hasColumn('user_profiles', 'gender')) {
                Schema::table('user_profiles', function (Blueprint $table) {
                    $table->index(['latitude', 'longitude', 'gender'], 'profiles_geo_gender_index');
                });
            }
        }

        // Optimize Proximity Artifacts for Feed queries
        if (Schema::hasTable('proximity_artifacts')) {
            if (! $indexExists('proximity_artifacts', 'artifacts_geo_active_index')) {
                Schema::table('proximity_artifacts', function (Blueprint $table) {
                    // Covering index for the main feed query: active, clean, within box
                    $table->index(['location_lat', 'location_lng', 'moderation_status', 'expires_at'], 'artifacts_geo_active_index');
                });
            }
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
