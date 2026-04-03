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
        $driver = \Illuminate\Support\Facades\DB::getDriverName();

        // Remove Bloat Tables (Governance, Federation, Economy, AI, Social, Merchant)
        // Child tables first to avoid FK constraint issues in SQLite
        $tablesToDrop = [
            'governance_votes',
            'governance_appeals',
            'governance_proposals',
            'global_bans',
            'federated_actor_reputations',
            'federated_posts',
            'federated_instances',
            'followers',
            'followings',
            'swap_transactions',
            'inventory_redemptions',
            'merchant_inventories',
            'merchant_promotions',
            'promotions',
            'merchant_profiles',
            'cat_photo_ratings',
            'cat_photos',
            'ai_avatars',
            'wingman_assists',
            'bounty_suggestions',
            'bounties',
            'user_gifts',
            'gifts',
            'token_transactions',
            'subscription_plans',
            'subscriptions',
            'event_invitations',
            'event_rsvps',
            'event_groups',
            'events',
            'group_members',
            'group_posts',
            'groups',
            'bulletin_messages',
            'bulletin_subscriptions',
            'bulletin_boards',
            'proximity_artifact_comments',
            'proximity_artifact_votes',
            'proximity_artifacts',
            'proximity_chatroom_members',
            'proximity_chatroom_messages',
            'proximity_chatrooms',
            'chatroom_members',
            'chatroom_messages',
            'chatrooms',
            'audio_room_members',
            'audio_rooms',
            'video_call_signals',
            'video_calls',
            'journals',
            'topics',
            'relationship_links',
            'referral_commissions',
            'user_achievements',
            'achievements',
            'share_unlocks',
            'photo_unlocks',
            'content_unlocks',
            'site_settings',
        ];

        // Disable foreign key checks temporarily to drop safely
        if ($driver === 'mysql') {
            \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        }

        foreach ($tablesToDrop as $table) {
            Schema::dropIfExists($table);
        }

        // Drop unused columns in users / user_profiles
        if (Schema::hasColumn('user_profiles', 'is_federated')) {
            Schema::table('user_profiles', function (Blueprint $table) use ($driver) {
                if ($driver === 'sqlite') {
                    try { \Illuminate\Support\Facades\DB::statement('DROP INDEX IF EXISTS user_profiles_is_federated_index'); } catch (\Exception $e) {}
                    try { \Illuminate\Support\Facades\DB::statement('DROP INDEX IF EXISTS user_profiles_journal_circle_group_id_index'); } catch (\Exception $e) {}
                } else {
                    try { $table->dropIndex(['is_federated']); } catch (\Exception $e) {}
                    try { $table->dropIndex(['journal_circle_group_id']); } catch (\Exception $e) {}
                }
                
                try { $table->dropForeign(['journal_circle_group_id']); } catch (\Exception $e) {}
                $table->dropColumn(['is_federated', 'is_confessional_mode', 'journal_visibility_default', 'journal_circle_group_id']);
            });
        }

        if (Schema::hasColumn('users', 'token_balance')) {
            Schema::table('users', function (Blueprint $table) use ($driver) {
                // Drop indices first to satisfy SQLite
                if ($driver === 'sqlite') {
                    try { \Illuminate\Support\Facades\DB::statement('DROP INDEX IF EXISTS users_token_balance_index'); } catch (\Exception $e) {}
                    try { \Illuminate\Support\Facades\DB::statement('DROP INDEX IF EXISTS users_referrer_id_index'); } catch (\Exception $e) {}
                    try { \Illuminate\Support\Facades\DB::statement('DROP INDEX IF EXISTS users_referral_code_index'); } catch (\Exception $e) {}
                    try { \Illuminate\Support\Facades\DB::statement('DROP INDEX IF EXISTS users_referral_code_unique'); } catch (\Exception $e) {}
                } else {
                    try { $table->dropIndex(['token_balance']); } catch (\Exception $e) {}
                    try { $table->dropIndex(['referrer_id']); } catch (\Exception $e) {}
                    try { $table->dropIndex(['referral_code']); } catch (\Exception $e) {}
                }

                if (Schema::hasColumn('users', 'referrer_id')) {
                    try { $table->dropForeign(['referrer_id']); } catch (\Exception $e) {}
                }
                $columns = ['token_balance', 'current_streak', 'last_daily_bonus_at', 'streak_just_updated', 'referrer_id', 'referral_code'];
                foreach ($columns as $col) {
                    if (Schema::hasColumn('users', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if ($driver === 'mysql') {
            \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reversal of "The Great Simplification" is out of scope.
        // If needed, run `php artisan migrate:fresh` to rebuild from old schema.
    }
};
