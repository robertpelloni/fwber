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
        if (Schema::hasColumn('events', 'type')) {
            return;
        }

        try {
            Schema::table('events', function (Blueprint $table) {
                $table->enum('type', ['standard', 'speed_dating', 'party', 'meetup', 'workshop'])->default('standard')->after('description');
            });
        } catch (\Exception $e) {
            // Ignore duplicate column error if race condition occurs
            if (!str_contains($e->getMessage(), 'Duplicate column name')) {
                throw $e;
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
