<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ensure the group_members table exists before altering
        if (Schema::hasTable('group_members')) {
            Schema::table('group_members', function (Blueprint $table) {
                if (!Schema::hasColumn('group_members', 'is_banned')) {
                    $table->boolean('is_banned')->default(false)->after('is_muted');
                }
                if (!Schema::hasColumn('group_members', 'role_changed_at')) {
                    $table->timestamp('role_changed_at')->nullable()->after('joined_at');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('group_members')) {
            Schema::table('group_members', function (Blueprint $table) {
                if (Schema::hasColumn('group_members', 'is_banned')) {
                    $table->dropColumn('is_banned');
                }
                if (Schema::hasColumn('group_members', 'role_changed_at')) {
                    $table->dropColumn('role_changed_at');
                }
            });
        }
    }
};
