<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Update groups table
        Schema::table('groups', function (Blueprint $table) {
            if (!Schema::hasColumn('groups', 'icon')) {
                $table->string('icon')->nullable()->after('description');
            }
            if (!Schema::hasColumn('groups', 'privacy')) {
                $table->enum('privacy', ['public', 'private'])->default('public')->after('icon');
                $table->index('privacy');
            }
            if (!Schema::hasColumn('groups', 'member_count')) {
                $table->integer('member_count')->default(0)->after('privacy');
            }
            if (!Schema::hasColumn('groups', 'created_by_user_id')) {
                // Assuming creator_id exists, we might want to alias or use it.
                // If creator_id exists, we can use it. The user asked for created_by_user_id.
                // Let's add it if it doesn't exist, or we can assume creator_id is the one.
                // For strict compliance, I'll add created_by_user_id and maybe migrate data if needed.
                // But having two columns is messy. I'll check if creator_id exists (it does per model).
                // I'll add created_by_user_id as a foreign key.
                $table->foreignId('created_by_user_id')->nullable()->constrained('users');
                $table->index('created_by_user_id');
            }
        });

        // Update group_members table
        Schema::table('group_members', function (Blueprint $table) {
            if (!Schema::hasColumn('group_members', 'role')) {
                $table->enum('role', ['admin', 'moderator', 'member'])->default('member');
            }
            if (!Schema::hasColumn('group_members', 'joined_at')) {
                $table->timestamp('joined_at')->useCurrent();
            }
            // Unique index on group_id, user_id usually exists, but let's ensure
            // $table->unique(['group_id', 'user_id']); // Likely already exists
        });

        // Create group_posts table
        if (!Schema::hasTable('group_posts')) {
            Schema::create('group_posts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('group_id')->constrained()->onDelete('cascade');
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->text('content');
                $table->timestamps();

                $table->index('group_id');
                $table->index('created_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('group_posts');
        
        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn(['icon', 'privacy', 'member_count', 'created_by_user_id']);
        });

        Schema::table('group_members', function (Blueprint $table) {
            $table->dropColumn(['role', 'joined_at']);
        });
    }
};
