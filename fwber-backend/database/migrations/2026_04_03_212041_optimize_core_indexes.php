<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Some deploy targets partially applied this migration before failing, leaving
     * behind one or more indexes. Re-running the raw `$table->index(...)` calls
     * would then explode with MySQL duplicate-key errors such as:
     * `Duplicate key name 'idx_user_profiles_location'`.
     *
     * The migration is therefore intentionally idempotent: each index is added
     * only when it does not already exist on the active connection.
     */
    public function up(): void
    {
        // 1. user_profiles
        $this->addIndexIfMissing('user_profiles', 'idx_user_profiles_location', ['latitude', 'longitude'], fn (Blueprint $table) => $table->index(['latitude', 'longitude'], 'idx_user_profiles_location'));
        $this->addIndexIfMissing('user_profiles', 'idx_user_profiles_travel_location', ['travel_latitude', 'travel_longitude'], fn (Blueprint $table) => $table->index(['travel_latitude', 'travel_longitude'], 'idx_user_profiles_travel_location'));
        $this->addIndexIfMissing('user_profiles', 'idx_user_profiles_gender', ['gender'], fn (Blueprint $table) => $table->index('gender', 'idx_user_profiles_gender'));
        $this->addIndexIfMissing('user_profiles', 'idx_user_profiles_birthdate', ['birthdate'], fn (Blueprint $table) => $table->index('birthdate', 'idx_user_profiles_birthdate'));
        $this->addIndexIfMissing('user_profiles', 'idx_user_profiles_travel_mode', ['is_travel_mode'], fn (Blueprint $table) => $table->index('is_travel_mode', 'idx_user_profiles_travel_mode'));
        $this->addIndexIfMissing('user_profiles', 'idx_user_profiles_matchmaking', ['gender', 'birthdate'], fn (Blueprint $table) => $table->index(['gender', 'birthdate'], 'idx_user_profiles_matchmaking'));

        // 2. user_matches
        $this->addIndexIfMissing('user_matches', 'idx_user_matches_user2', ['user2_id'], fn (Blueprint $table) => $table->index('user2_id', 'idx_user_matches_user2'));
        $this->addIndexIfMissing('user_matches', 'idx_user_matches_active', ['is_active'], fn (Blueprint $table) => $table->index('is_active', 'idx_user_matches_active'));
        $this->addIndexIfMissing('user_matches', 'idx_user_matches_u1_active', ['user1_id', 'is_active'], fn (Blueprint $table) => $table->index(['user1_id', 'is_active'], 'idx_user_matches_u1_active'));
        $this->addIndexIfMissing('user_matches', 'idx_user_matches_u2_active', ['user2_id', 'is_active'], fn (Blueprint $table) => $table->index(['user2_id', 'is_active'], 'idx_user_matches_u2_active'));

        // 3. match_actions
        $this->addIndexIfMissing('match_actions', 'idx_match_actions_target', ['target_user_id'], fn (Blueprint $table) => $table->index('target_user_id', 'idx_match_actions_target'));
        $this->addIndexIfMissing('match_actions', 'idx_match_actions_target_action', ['target_user_id', 'action'], fn (Blueprint $table) => $table->index(['target_user_id', 'action'], 'idx_match_actions_target_action'));

        // 4. messages
        $this->addIndexIfMissing('messages', 'idx_msgs_conversation', ['receiver_id', 'sender_id', 'created_at'], fn (Blueprint $table) => $table->index(['receiver_id', 'sender_id', 'created_at'], 'idx_msgs_conversation'));
        $this->addIndexIfMissing('messages', 'idx_msgs_unread', ['receiver_id', 'read_at'], fn (Blueprint $table) => $table->index(['receiver_id', 'read_at'], 'idx_msgs_unread'));
        $this->addIndexIfMissing('messages', 'idx_msgs_created', ['created_at'], fn (Blueprint $table) => $table->index('created_at', 'idx_msgs_created'));

        // 5. photos
        $this->addIndexIfMissing('photos', 'idx_photos_user_primary', ['user_id', 'is_primary'], fn (Blueprint $table) => $table->index(['user_id', 'is_primary'], 'idx_photos_user_primary'));
        $this->addIndexIfMissing('photos', 'idx_photos_user_order', ['user_id', 'order'], fn (Blueprint $table) => $table->index(['user_id', 'order'], 'idx_photos_user_order'));

        // 6. proximity_artifacts
        $this->addIndexIfMissing('proximity_artifacts', 'idx_artifacts_type', ['type'], fn (Blueprint $table) => $table->index('type', 'idx_artifacts_type'));
        $this->addIndexIfMissing('proximity_artifacts', 'idx_artifacts_expiry', ['expires_at'], fn (Blueprint $table) => $table->index('expires_at', 'idx_artifacts_expiry'));
        $this->addIndexIfMissing('proximity_artifacts', 'idx_artifacts_location', ['latitude', 'longitude'], fn (Blueprint $table) => $table->index(['latitude', 'longitude'], 'idx_artifacts_location'));
    }

    private function addIndexIfMissing(string $tableName, string $indexName, array $columns, callable $definition): void
    {
        if (! Schema::hasTable($tableName) || $this->hasIndex($tableName, $indexName) || ! $this->hasColumns($tableName, $columns)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($definition) {
            $definition($table);
        });
    }

    private function hasIndex(string $tableName, string $indexName): bool
    {
        $driver = DB::getDriverName();

        return match ($driver) {
            'mysql', 'mariadb' => (bool) DB::table('information_schema.statistics')
                ->whereRaw('table_schema = DATABASE()')
                ->where('table_name', $tableName)
                ->where('index_name', $indexName)
                ->exists(),
            'sqlite' => collect(DB::select("PRAGMA index_list('{$tableName}')"))
                ->contains(fn ($index) => ($index->name ?? null) === $indexName),
            'pgsql' => (bool) DB::table('pg_indexes')
                ->whereRaw('schemaname = current_schema()')
                ->where('tablename', $tableName)
                ->where('indexname', $indexName)
                ->exists(),
            default => false,
        };
    }

    private function hasColumns(string $tableName, array $columns): bool
    {
        foreach ($columns as $column) {
            if (! Schema::hasColumn($tableName, $column)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Out of scope for performance optimization rollback
    }
};
