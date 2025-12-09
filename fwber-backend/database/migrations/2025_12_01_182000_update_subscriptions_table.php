<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('subscriptions', 'user_id')) {
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
            }
            if (!Schema::hasColumn('subscriptions', 'name')) {
                $table->string('name');
            }
            if (!Schema::hasColumn('subscriptions', 'stripe_id')) {
                $table->string('stripe_id')->nullable();
            }
            if (!Schema::hasColumn('subscriptions', 'stripe_status')) {
                $table->string('stripe_status');
            }
            if (!Schema::hasColumn('subscriptions', 'stripe_price')) {
                $table->string('stripe_price')->nullable();
            }
            if (!Schema::hasColumn('subscriptions', 'quantity')) {
                $table->integer('quantity')->nullable();
            }
            if (!Schema::hasColumn('subscriptions', 'trial_ends_at')) {
                $table->timestamp('trial_ends_at')->nullable();
            }
            if (!Schema::hasColumn('subscriptions', 'ends_at')) {
                $table->timestamp('ends_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn([
                'user_id', 'name', 'stripe_id', 'stripe_status', 
                'stripe_price', 'quantity', 'trial_ends_at', 'ends_at'
            ]);
        });
    }
};
