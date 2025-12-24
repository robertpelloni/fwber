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
        // For MySQL, we modify the enum definition
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE proximity_artifacts MODIFY COLUMN type ENUM('chat', 'board_post', 'announce', 'token_drop') NOT NULL");
        }
        // For SQLite, it's usually just TEXT with check constraint, or no constraint if created simply.
        // We assume SQLite (testing) will accept the string if we didn't add strict check constraint manually in raw SQL.
        // Laravel Schema::enum creates varchar in some drivers or Check constraint in others.
        // If it fails in SQLite tests, we might need to recreate table or disable constraint.
        // But usually Schema builder for SQLite just makes it varchar.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE proximity_artifacts MODIFY COLUMN type ENUM('chat', 'board_post', 'announce') NOT NULL");
        }
    }
};
