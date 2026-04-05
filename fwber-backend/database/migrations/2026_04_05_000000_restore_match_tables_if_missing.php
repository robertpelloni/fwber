<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Repair live schema drift where the migration ledger says the matching
     * tables ran but `user_matches` (and sometimes `match_actions`) are absent.
     *
     * This migration is intentionally idempotent so it is safe on healthy
     * environments and only recreates the core matching tables when needed.
     */
    public function up(): void
    {
        if (! Schema::hasTable('user_matches')) {
            Schema::create('user_matches', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user1_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('user2_id')->constrained('users')->onDelete('cascade');
                $table->integer('match_score')->default(0);
                $table->boolean('nfc_verified')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['user1_id', 'user2_id']);
                $table->index('user2_id', 'idx_user_matches_user2');
                $table->index('is_active', 'idx_user_matches_active');
                $table->index(['user1_id', 'is_active'], 'idx_user_matches_u1_active');
                $table->index(['user2_id', 'is_active'], 'idx_user_matches_u2_active');
            });
        }

        if (! Schema::hasTable('match_actions')) {
            Schema::create('match_actions', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('target_user_id')->constrained('users')->onDelete('cascade');
                $table->enum('action', ['like', 'pass']);
                $table->timestamps();

                $table->unique(['user_id', 'target_user_id']);
            });
        }
    }

    public function down(): void
    {
        // This migration is a live repair step. Rolling it back would risk
        // deleting recovered production tables, so the down path is a no-op.
    }
};
