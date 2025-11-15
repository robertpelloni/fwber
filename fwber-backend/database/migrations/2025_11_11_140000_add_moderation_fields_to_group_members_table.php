<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('group_members')) {
            Schema::table('group_members', function (Blueprint $table) {
                if (!Schema::hasColumn('group_members', 'muted_until')) {
                    $table->timestamp('muted_until')->nullable()->after('is_muted');
                }
                if (!Schema::hasColumn('group_members', 'banned_reason')) {
                    $table->string('banned_reason', 255)->nullable()->after('is_banned');
                }
                if (!Schema::hasColumn('group_members', 'banned_at')) {
                    $table->timestamp('banned_at')->nullable()->after('banned_reason');
                }
                if (!Schema::hasColumn('group_members', 'banned_by_user_id')) {
                    $table->unsignedBigInteger('banned_by_user_id')->nullable()->after('banned_at');
                    $table->index('banned_by_user_id');
                }
                if (!Schema::hasColumn('group_members', 'mute_reason')) {
                    $table->string('mute_reason', 255)->nullable()->after('muted_until');
                }
                if (!Schema::hasColumn('group_members', 'muted_by_user_id')) {
                    $table->unsignedBigInteger('muted_by_user_id')->nullable()->after('mute_reason');
                    $table->index('muted_by_user_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('group_members')) {
            Schema::table('group_members', function (Blueprint $table) {
                if (Schema::hasColumn('group_members', 'muted_until')) {
                    $table->dropColumn('muted_until');
                }
                if (Schema::hasColumn('group_members', 'banned_reason')) {
                    $table->dropColumn('banned_reason');
                }
                if (Schema::hasColumn('group_members', 'banned_at')) {
                    $table->dropColumn('banned_at');
                }
                if (Schema::hasColumn('group_members', 'banned_by_user_id')) {
                    $table->dropIndex(['banned_by_user_id']);
                    $table->dropColumn('banned_by_user_id');
                }
                if (Schema::hasColumn('group_members', 'mute_reason')) {
                    $table->dropColumn('mute_reason');
                }
                if (Schema::hasColumn('group_members', 'muted_by_user_id')) {
                    $table->dropIndex(['muted_by_user_id']);
                    $table->dropColumn('muted_by_user_id');
                }
            });
        }
    }
};
