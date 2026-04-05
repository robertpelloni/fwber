<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Restore profile columns that discovery filters and profile editors already
     * reference in the active product.
     *
     * Why this exists:
     * - the frontend already exposes dietary, politics, and religion controls
     * - AIMatchingService already tries to filter on these columns
     * - simplified migrations had dropped them from the active schema
     *
     * We add them defensively so deploy retries and schema-drifted databases do
     * not explode when this migration is re-run in real environments.
     */
    public function up(): void
    {
        if (! Schema::hasTable('user_profiles')) {
            return;
        }

        Schema::table('user_profiles', function (Blueprint $table): void {
            if (! Schema::hasColumn('user_profiles', 'dietary_preferences')) {
                $table->string('dietary_preferences')->nullable()->after('cannabis_status');
            }

            if (! Schema::hasColumn('user_profiles', 'religion')) {
                $table->string('religion')->nullable()->after('zodiac_sign');
            }

            if (! Schema::hasColumn('user_profiles', 'political_views')) {
                $table->string('political_views')->nullable()->after('religion');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('user_profiles')) {
            return;
        }

        Schema::table('user_profiles', function (Blueprint $table): void {
            $columnsToDrop = array_values(array_filter([
                Schema::hasColumn('user_profiles', 'dietary_preferences') ? 'dietary_preferences' : null,
                Schema::hasColumn('user_profiles', 'religion') ? 'religion' : null,
                Schema::hasColumn('user_profiles', 'political_views') ? 'political_views' : null,
            ]));

            if ($columnsToDrop !== []) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
