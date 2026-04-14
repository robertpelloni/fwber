<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Stage 2 repair migration to eliminate remaining 500 errors.
     * 
     * Targets missing token transaction and achievement tables, plus 
     * missing streak/bonus columns on the users table.
     */
    public function up(): void
    {
        // --- 1. USER COLUMN REPAIR ---

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'current_streak')) {
                $table->integer('current_streak')->default(0)->after('token_balance');
            }
            if (! Schema::hasColumn('users', 'last_daily_bonus_at')) {
                $table->timestamp('last_daily_bonus_at')->nullable()->after('current_streak');
            }
        });

        // --- 2. TOKEN TRANSACTIONS ---

        if (! Schema::hasTable('token_transactions')) {
            Schema::create('token_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->decimal('amount', 12, 4);
                $table->string('type'); // signup_bonus, referral_bonus, spend, etc.
                $table->string('description')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                
                $table->index('user_id');
                $table->index('type');
            });
        }

        // --- 3. ACHIEVEMENTS ---

        if (! Schema::hasTable('achievements')) {
            Schema::create('achievements', function (Blueprint $table) {
                $table->id();
                $table->string('slug')->unique();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('icon')->nullable();
                $table->integer('token_reward')->default(0);
                $table->json('criteria')->nullable();
                $table->timestamps();
            });

            // Seed basic achievements
            DB::table('achievements')->insertOrIgnore([
                ['slug' => 'profile_verified', 'name' => 'Verified Human', 'token_reward' => 50, 'created_at' => now(), 'updated_at' => now()],
                ['slug' => 'first_match', 'name' => 'First Connection', 'token_reward' => 10, 'created_at' => now(), 'updated_at' => now()],
                ['slug' => 'streak_7', 'name' => 'Week on Fire', 'token_reward' => 100, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        if (! Schema::hasTable('user_achievements')) {
            Schema::create('user_achievements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('achievement_id')->constrained()->onDelete('cascade');
                $table->timestamp('unlocked_at')->useCurrent();
                $table->timestamps();

                $table->unique(['user_id', 'achievement_id']);
            });
        }
    }

    public function down(): void
    {
        // This is a recovery migration.
    }
};
