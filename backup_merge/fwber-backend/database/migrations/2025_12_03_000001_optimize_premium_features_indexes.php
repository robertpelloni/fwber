<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $addIndex = function($table, $columns, $name) {
            if (Schema::hasTable($table)) {
                try {
                    // Attempt to add index, will fail if exists
                    Schema::table($table, function (Blueprint $table) use ($columns, $name) {
                        $table->index($columns, $name);
                    });
                } catch (\Exception $e) {
                    // Index likely exists or column missing
                }
            }
        };

        // Subscriptions
        $addIndex('subscriptions', ['user_id', 'stripe_status'], 'subscriptions_user_status_index');
        $addIndex('subscriptions', 'stripe_id', 'subscriptions_stripe_id_index');
        $addIndex('subscriptions', 'ends_at', 'subscriptions_ends_at_index');

        // Boosts
        $addIndex('boosts', 'expires_at', 'boosts_expires_at_index');
        $addIndex('boosts', ['boost_type', 'created_at'], 'boosts_type_created_index');

        // Events
        $addIndex('events', ['status', 'starts_at'], 'events_status_starts_at_index');
        $addIndex('events', 'created_by_user_id', 'events_creator_index');
        $addIndex('events', ['latitude', 'longitude', 'starts_at'], 'events_geo_time_index');

        // Event Attendees
        $addIndex('event_attendees', ['user_id', 'status'], 'event_attendees_user_status_index');
        $addIndex('event_attendees', ['event_id', 'status'], 'event_attendees_event_status_index');

        // Groups
        $addIndex('groups', ['privacy', 'visibility'], 'groups_privacy_visibility_index');
        $addIndex('groups', 'member_count', 'groups_member_count_index');
        $addIndex('groups', 'visibility', 'groups_visibility_index');

        // Group Members
        $addIndex('group_members', ['user_id', 'role'], 'group_members_user_role_index');
        $addIndex('group_members', ['group_id', 'role'], 'group_members_group_role_index');
        $addIndex('group_members', 'joined_at', 'group_members_joined_at_index');

        // Group Posts
        $addIndex('group_posts', ['user_id', 'created_at'], 'group_posts_user_created_index');

        // Payments
        $addIndex('payments', ['user_id', 'created_at'], 'payments_user_created_index');
        $addIndex('payments', 'transaction_id', 'payments_transaction_id_index');
        $addIndex('payments', ['status', 'created_at'], 'payments_status_created_index');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $dropIndex = function($table, $name) {
            if (Schema::hasTable($table)) {
                try {
                    Schema::table($table, function (Blueprint $table) use ($name) {
                        $table->dropIndex($name);
                    });
                } catch (\Exception $e) {}
            }
        };

        $dropIndex('subscriptions', 'subscriptions_user_status_index');
        $dropIndex('subscriptions', 'subscriptions_stripe_id_index');
        $dropIndex('subscriptions', 'subscriptions_ends_at_index');

        $dropIndex('boosts', 'boosts_expires_at_index');
        $dropIndex('boosts', 'boosts_type_created_index');

        $dropIndex('events', 'events_status_starts_at_index');
        $dropIndex('events', 'events_creator_index');
        $dropIndex('events', 'events_geo_time_index');

        $dropIndex('event_attendees', 'event_attendees_user_status_index');
        $dropIndex('event_attendees', 'event_attendees_event_status_index');

        $dropIndex('groups', 'groups_privacy_visibility_index');
        $dropIndex('groups', 'groups_member_count_index');
        $dropIndex('groups', 'groups_visibility_index');

        $dropIndex('group_members', 'group_members_user_role_index');
        $dropIndex('group_members', 'group_members_group_role_index');
        $dropIndex('group_members', 'group_members_joined_at_index');

        $dropIndex('group_posts', 'group_posts_user_created_index');

        $dropIndex('payments', 'payments_user_created_index');
        $dropIndex('payments', 'payments_transaction_id_index');
        $dropIndex('payments', 'payments_status_created_index');
    }
};
