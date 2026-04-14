<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Comprehensive repair migration to eliminate 500 errors caused by schema drift.
     * 
     * The production database has 'ghost' migration entries for tables that were 
     * dropped during product simplification but whose migration ledger entries 
     * were not cleared. This migration ensures every required table and column 
     * from the user-approved restoration scope is present.
     */
    public function up(): void
    {
        // --- 1. CORE OPERATIONAL TABLES ---

        if (! Schema::hasTable('site_settings')) {
            Schema::create('site_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->string('value');
                $table->string('type')->default('string'); 
                $table->string('description')->nullable();
                $table->timestamps();
            });

            // Seed initial critical settings
            DB::table('site_settings')->insertOrIgnore([
                ['key' => 'daily_token_bonus', 'value' => '10.0', 'type' => 'float', 'description' => 'Tokens awarded for daily login'],
                ['key' => 'proposal_min_tokens', 'value' => '100', 'type' => 'int', 'description' => 'Minimum tokens to create a proposal'],
                ['key' => 'vote_participation_min', 'value' => '10', 'type' => 'int', 'description' => 'Minimum tokens to vote'],
            ]);
        }

        // --- 2. GOVERNANCE & TRUST TABLES (Required by active middleware) ---

        if (! Schema::hasTable('governance_proposals')) {
            Schema::create('governance_proposals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('creator_id')->constrained('users')->onDelete('cascade');
                $table->string('title');
                $table->text('description');
                $table->string('category')->default('policy');
                $table->json('options');
                $table->decimal('min_tokens_required', 12, 4)->default(10.0000);
                $table->timestamp('starts_at')->useCurrent();
                $table->timestamp('expires_at')->nullable();
                $table->string('status')->default('active');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('global_bans')) {
            Schema::create('global_bans', function (Blueprint $table) {
                $table->id();
                $table->string('bannable_identifier')->unique()->index();
                $table->string('type')->default('user'); 
                $table->string('reason')->nullable();
                $table->foreignId('proposal_id')->nullable()->constrained('governance_proposals')->onDelete('set null');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('governance_votes')) {
            Schema::create('governance_votes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('governance_proposal_id')->constrained()->onDelete('cascade');
                $table->integer('option_index');
                $table->decimal('token_weight', 12, 4);
                $table->timestamps();
                $table->unique(['user_id', 'governance_proposal_id'], 'user_proposal_unique_vote');
            });
        }

        if (! Schema::hasTable('governance_appeals')) {
            Schema::create('governance_appeals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->text('reason');
                $table->string('status')->default('pending');
                $table->foreignId('proposal_id')->nullable()->constrained('governance_proposals')->onDelete('set null');
                $table->timestamps();
            });
        }

        // --- 3. ECONOMY & FEDERATION TABLES ---

        if (! Schema::hasTable('swap_transactions')) {
            Schema::create('swap_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('source_asset')->default('FWB_TOKEN');
                $table->string('target_asset'); 
                $table->decimal('source_amount', 12, 4);
                $table->decimal('target_amount', 12, 4)->nullable();
                $table->string('destination_address')->nullable();
                $table->string('status')->default('pending');
                $table->string('tx_hash')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('federated_instances')) {
            Schema::create('federated_instances', function (Blueprint $table) {
                $table->id();
                $table->string('domain')->unique();
                $table->string('actor_uri')->nullable();
                $table->string('software')->default('fwber');
                $table->string('status')->default('active');
                $table->boolean('relay_enabled')->default(true);
                $table->timestamp('last_synced_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('federated_actor_reputations')) {
            Schema::create('federated_actor_reputations', function (Blueprint $table) {
                $table->id();
                $table->string('actor_uri')->unique()->index();
                $table->integer('vouch_count')->default(0);
                $table->timestamp('member_since')->nullable();
                $table->json('reputation_metadata')->nullable();
                $table->timestamp('last_synced_at')->useCurrent();
                $table->timestamps();
            });
        }

        // --- 4. PROFILE COLUMN REPAIR ---

        Schema::table('user_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('user_profiles', 'is_federated')) {
                $table->boolean('is_federated')->default(false)->after('avatar_url');
            }
            if (! Schema::hasColumn('user_profiles', 'journal_circle_group_id')) {
                $table->unsignedBigInteger('journal_circle_group_id')->nullable()->after('is_federated');
            }
        });

        // Ensure followings table is aware of federated actors
        if (Schema::hasTable('followings')) {
            Schema::table('followings', function (Blueprint $table) {
                if (! Schema::hasColumn('followings', 'actor_type')) {
                    $table->string('actor_type')->default('user')->after('following_id');
                }
            });
        }
    }

    /**
     * Reverse the repair.
     */
    public function down(): void
    {
        // This is a recovery migration; rolling back would re-trigger 500 errors.
    }
};
