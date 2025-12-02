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
        $indexExists = function ($table, $indexName) {
            try {
                $driver = DB::connection()->getDriverName();
                if ($driver === 'sqlite') {
                    $indexes = DB::select("PRAGMA index_list('{$table}')");
                    return collect($indexes)->pluck('name')->contains($indexName);
                }
                return collect(DB::select("SHOW INDEXES FROM {$table} WHERE Key_name = ?", [$indexName]))->count() > 0;
            } catch (\Exception $e) {
                return false;
            }
        };

        // Optimize Subscriptions table
        if (Schema::hasTable('subscriptions')) {
            Schema::table('subscriptions', function (Blueprint $table) use ($indexExists) {
                // Index for looking up active subscriptions by user
                if (!$indexExists('subscriptions', 'subscriptions_user_status_index')) {
                    if (Schema::hasColumn('subscriptions', 'user_id') && Schema::hasColumn('subscriptions', 'stripe_status')) {
                        $table->index(['user_id', 'stripe_status'], 'subscriptions_user_status_index');
                    }
                }
                
                // Index for Stripe webhook lookups
                if (!$indexExists('subscriptions', 'subscriptions_stripe_id_index')) {
                    if (Schema::hasColumn('subscriptions', 'stripe_id')) {
                        $table->index('stripe_id', 'subscriptions_stripe_id_index');
                    }
                }
                
                // Index for expiring subscriptions (background jobs)
                if (!$indexExists('subscriptions', 'subscriptions_ends_at_index')) {
                    if (Schema::hasColumn('subscriptions', 'ends_at')) {
                        $table->index('ends_at', 'subscriptions_ends_at_index');
                    }
                }
            });
        }

        // Optimize Boosts table (already has user_id + status composite)
        if (Schema::hasTable('boosts')) {
            Schema::table('boosts', function (Blueprint $table) use ($indexExists) {
                // Index for expiring boosts (background jobs)
                if (!$indexExists('boosts', 'boosts_expires_at_index')) {
                    if (Schema::hasColumn('boosts', 'expires_at')) {
                        $table->index('expires_at', 'boosts_expires_at_index');
                    }
                }
                
                // Index for boost type analytics
                if (!$indexExists('boosts', 'boosts_type_created_index')) {
                    if (Schema::hasColumn('boosts', 'boost_type') && Schema::hasColumn('boosts', 'created_at')) {
                        $table->index(['boost_type', 'created_at'], 'boosts_type_created_index');
                    }
                }
            });
        }

        // Optimize Events table
        if (Schema::hasTable('events')) {
            Schema::table('events', function (Blueprint $table) use ($indexExists) {
                // Index for event listings by status and time
                if (!$indexExists('events', 'events_status_starts_at_index')) {
                    if (Schema::hasColumn('events', 'status') && Schema::hasColumn('events', 'starts_at')) {
                        $table->index(['status', 'starts_at'], 'events_status_starts_at_index');
                    }
                }
                
                // Index for user's created events
                if (!$indexExists('events', 'events_creator_index')) {
                    if (Schema::hasColumn('events', 'created_by_user_id')) {
                        $table->index('created_by_user_id', 'events_creator_index');
                    }
                }
                
                // Composite index for geo + time queries
                if (!$indexExists('events', 'events_geo_time_index')) {
                    if (Schema::hasColumn('events', 'latitude') && Schema::hasColumn('events', 'longitude') && Schema::hasColumn('events', 'starts_at')) {
                        $table->index(['latitude', 'longitude', 'starts_at'], 'events_geo_time_index');
                    }
                }
            });
        }

        // Optimize Event Attendees table
        if (Schema::hasTable('event_attendees')) {
            Schema::table('event_attendees', function (Blueprint $table) use ($indexExists) {
                // Index for user's attended events
                if (!$indexExists('event_attendees', 'event_attendees_user_status_index')) {
                    if (Schema::hasColumn('event_attendees', 'user_id') && Schema::hasColumn('event_attendees', 'status')) {
                        $table->index(['user_id', 'status'], 'event_attendees_user_status_index');
                    }
                }
                
                // Index for event's attendees by status
                if (!$indexExists('event_attendees', 'event_attendees_event_status_index')) {
                    if (Schema::hasColumn('event_attendees', 'event_id') && Schema::hasColumn('event_attendees', 'status')) {
                        $table->index(['event_id', 'status'], 'event_attendees_event_status_index');
                    }
                }
            });
        }

        // Optimize Groups table
        if (Schema::hasTable('groups')) {
            Schema::table('groups', function (Blueprint $table) use ($indexExists) {
                // Index for group listings by privacy and activity
                if (!$indexExists('groups', 'groups_privacy_visibility_index')) {
                    if (Schema::hasColumn('groups', 'privacy') && Schema::hasColumn('groups', 'visibility')) {
                        $table->index(['privacy', 'visibility'], 'groups_privacy_visibility_index');
                    }
                }
                
                // Index for popular groups
                if (!$indexExists('groups', 'groups_member_count_index')) {
                    if (Schema::hasColumn('groups', 'member_count')) {
                        $table->index('member_count', 'groups_member_count_index');
                    }
                }
                
                // Index for active groups
                if (!$indexExists('groups', 'groups_visibility_index')) {
                    if (Schema::hasColumn('groups', 'visibility')) {
                        $table->index('visibility', 'groups_visibility_index');
                    }
                }
            });
        }

        // Optimize Group Members table
        if (Schema::hasTable('group_members')) {
            Schema::table('group_members', function (Blueprint $table) use ($indexExists) {
                // Index for user's groups
                if (!$indexExists('group_members', 'group_members_user_role_index')) {
                    if (Schema::hasColumn('group_members', 'user_id') && Schema::hasColumn('group_members', 'role')) {
                        $table->index(['user_id', 'role'], 'group_members_user_role_index');
                    }
                }
                
                // Index for group moderators/admins
                if (!$indexExists('group_members', 'group_members_group_role_index')) {
                    if (Schema::hasColumn('group_members', 'group_id') && Schema::hasColumn('group_members', 'role')) {
                        $table->index(['group_id', 'role'], 'group_members_group_role_index');
                    }
                }
                
                // Index for recent joins
                if (!$indexExists('group_members', 'group_members_joined_at_index')) {
                    if (Schema::hasColumn('group_members', 'joined_at')) {
                        $table->index('joined_at', 'group_members_joined_at_index');
                    }
                }
            });
        }

        // Optimize Group Posts table
        if (Schema::hasTable('group_posts')) {
            Schema::table('group_posts', function (Blueprint $table) use ($indexExists) {
                // Index for user's posts across groups
                if (!$indexExists('group_posts', 'group_posts_user_created_index')) {
                    if (Schema::hasColumn('group_posts', 'user_id') && Schema::hasColumn('group_posts', 'created_at')) {
                        $table->index(['user_id', 'created_at'], 'group_posts_user_created_index');
                    }
                }
            });
        }

        // Optimize Payments table
        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) use ($indexExists) {
                // Index for user's payment history
                if (!$indexExists('payments', 'payments_user_created_index')) {
                    if (Schema::hasColumn('payments', 'user_id') && Schema::hasColumn('payments', 'created_at')) {
                        $table->index(['user_id', 'created_at'], 'payments_user_created_index');
                    }
                }
                
                // Index for transaction lookups
                if (!$indexExists('payments', 'payments_transaction_id_index')) {
                    if (Schema::hasColumn('payments', 'transaction_id')) {
                        $table->index('transaction_id', 'payments_transaction_id_index');
                    }
                }
                
                // Index for payment status monitoring
                if (!$indexExists('payments', 'payments_status_created_index')) {
                    if (Schema::hasColumn('payments', 'status') && Schema::hasColumn('payments', 'created_at')) {
                        $table->index(['status', 'created_at'], 'payments_status_created_index');
                    }
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('subscriptions')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->dropIndex('subscriptions_user_status_index');
                $table->dropIndex('subscriptions_stripe_id_index');
                $table->dropIndex('subscriptions_ends_at_index');
            });
        }

        if (Schema::hasTable('boosts')) {
            Schema::table('boosts', function (Blueprint $table) {
                $table->dropIndex('boosts_expires_at_index');
                $table->dropIndex('boosts_type_created_index');
            });
        }

        if (Schema::hasTable('events')) {
            Schema::table('events', function (Blueprint $table) {
                $table->dropIndex('events_status_starts_at_index');
                $table->dropIndex('events_creator_index');
                $table->dropIndex('events_geo_time_index');
            });
        }

        if (Schema::hasTable('event_attendees')) {
            Schema::table('event_attendees', function (Blueprint $table) {
                $table->dropIndex('event_attendees_user_status_index');
                $table->dropIndex('event_attendees_event_status_index');
            });
        }

        if (Schema::hasTable('groups')) {
            Schema::table('groups', function (Blueprint $table) {
                $table->dropIndex('groups_privacy_visibility_index');
                $table->dropIndex('groups_member_count_index');
                $table->dropIndex('groups_visibility_index');
            });
        }

        if (Schema::hasTable('group_members')) {
            Schema::table('group_members', function (Blueprint $table) {
                $table->dropIndex('group_members_user_role_index');
                $table->dropIndex('group_members_group_role_index');
                $table->dropIndex('group_members_joined_at_index');
            });
        }

        if (Schema::hasTable('group_posts')) {
            Schema::table('group_posts', function (Blueprint $table) {
                $table->dropIndex('group_posts_user_created_index');
            });
        }

        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropIndex('payments_user_created_index');
                $table->dropIndex('payments_transaction_id_index');
                $table->dropIndex('payments_status_created_index');
            });
        }
    }
};
