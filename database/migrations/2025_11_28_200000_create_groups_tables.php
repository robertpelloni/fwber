<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Create groups table
        if (!Schema::hasTable('groups')) {
            Schema::create('groups', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('icon')->nullable();
                $table->enum('privacy', ['public', 'private'])->default('public');
                $table->enum('visibility', ['visible', 'hidden'])->default('visible');
                $table->foreignId('created_by_user_id')->nullable()->constrained('users');
                $table->foreignId('creator_id')->nullable()->constrained('users');
                $table->integer('member_count')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                
                $table->index('privacy');
                $table->index('created_by_user_id');
            });
        } else {
            // Update groups table if it exists (fallback for partial migrations)
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
                    $table->foreignId('created_by_user_id')->nullable()->constrained('users');
                    $table->index('created_by_user_id');
                }
            });
        }

        // Create group_members table
        if (!Schema::hasTable('group_members')) {
            Schema::create('group_members', function (Blueprint $table) {
                $table->id();
                $table->foreignId('group_id')->constrained()->onDelete('cascade');
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->enum('role', ['admin', 'moderator', 'member'])->default('member');
                $table->timestamp('joined_at')->useCurrent();
                $table->timestamps();
                
                $table->unique(['group_id', 'user_id']);
            });
        } else {
            // Update group_members table
            Schema::table('group_members', function (Blueprint $table) {
                if (!Schema::hasColumn('group_members', 'role')) {
                    $table->enum('role', ['admin', 'moderator', 'member'])->default('member');
                }
                if (!Schema::hasColumn('group_members', 'joined_at')) {
                    $table->timestamp('joined_at')->useCurrent();
                }
            });
        }

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
        Schema::dropIfExists('group_members');
        Schema::dropIfExists('groups');
    }
};
