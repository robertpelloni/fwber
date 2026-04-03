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
        // Remove Bloat Tables (Governance, Federation, Economy, AI, Social, Merchant)
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
            'merchant_inventories',
            'inventory_redemptions',
            'merchant_promotions',
            'promotions',
            'merchant_profiles',
            'cat_photos',
            'cat_photo_ratings',
            'ai_avatars',
            'wingman_assists',
            'bounties',
            'bounty_suggestions',
            'gifts',
            'user_gifts',
            'token_transactions',
            'subscriptions',
            'subscription_plans',
            'event_groups',
            'events',
            'event_invitations',
            'event_rsvps',
            'groups',
            'group_members',
            'group_posts',
            'bulletin_boards',
            'bulletin_messages',
            'bulletin_subscriptions',
            'proximity_artifacts',
            'proximity_artifact_comments',
            'proximity_artifact_votes',
            'proximity_chatrooms',
            'proximity_chatroom_messages',
            'proximity_chatroom_members',
            'chatrooms',
            'chatroom_members',
            'chatroom_messages',
            'audio_rooms',
            'audio_room_members',
            'video_calls',
            'video_call_signals',
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
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        foreach ($tablesToDrop as $table) {
            Schema::dropIfExists($table);
        }

        // Drop unused columns in users / user_profiles
        if (Schema::hasColumn('user_profiles', 'is_federated')) {
            Schema::table('user_profiles', function (Blueprint $table) {
                $table->dropForeign(['journal_circle_group_id']);
                $table->dropColumn(['is_federated', 'is_confessional_mode', 'journal_visibility_default', 'journal_circle_group_id']);
            });
        }

        if (Schema::hasColumn('users', 'token_balance')) {
            Schema::table('users', function (Blueprint $table) {
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

        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1;');
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
