<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            if (!Schema::hasColumn('groups', 'max_members')) {
                $table->integer('max_members')->default(100)->after('member_count');
            }
            if (!Schema::hasColumn('groups', 'chatroom_id')) {
                $table->foreignId('chatroom_id')->nullable()->constrained('chatrooms')->nullOnDelete()->after('max_members');
            }
        });

        Schema::table('group_members', function (Blueprint $table) {
            if (!Schema::hasColumn('group_members', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
            if (!Schema::hasColumn('group_members', 'is_banned')) {
                $table->boolean('is_banned')->default(false);
            }
            if (!Schema::hasColumn('group_members', 'banned_at')) {
                $table->timestamp('banned_at')->nullable();
            }
            if (!Schema::hasColumn('group_members', 'banned_by_user_id')) {
                $table->foreignId('banned_by_user_id')->nullable()->constrained('users');
            }
            if (!Schema::hasColumn('group_members', 'banned_reason')) {
                $table->string('banned_reason')->nullable();
            }
            if (!Schema::hasColumn('group_members', 'is_muted')) {
                $table->boolean('is_muted')->default(false);
            }
            if (!Schema::hasColumn('group_members', 'muted_until')) {
                $table->timestamp('muted_until')->nullable();
            }
            if (!Schema::hasColumn('group_members', 'mute_reason')) {
                $table->string('mute_reason')->nullable();
            }
            if (!Schema::hasColumn('group_members', 'muted_by_user_id')) {
                $table->foreignId('muted_by_user_id')->nullable()->constrained('users');
            }
            if (!Schema::hasColumn('group_members', 'left_at')) {
                $table->timestamp('left_at')->nullable();
            }
            if (!Schema::hasColumn('group_members', 'role_changed_at')) {
                $table->timestamp('role_changed_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('group_members', function (Blueprint $table) {
            $table->dropColumn([
                'is_active', 'is_banned', 'banned_at', 'banned_by_user_id', 'banned_reason',
                'is_muted', 'muted_until', 'mute_reason', 'muted_by_user_id', 'left_at', 'role_changed_at'
            ]);
        });

        Schema::table('groups', function (Blueprint $table) {
            if (Schema::hasColumn('groups', 'chatroom_id')) {
                $table->dropForeign(['chatroom_id']);
                $table->dropColumn('chatroom_id');
            }
            if (Schema::hasColumn('groups', 'max_members')) {
                $table->dropColumn('max_members');
            }
        });
    }
};
