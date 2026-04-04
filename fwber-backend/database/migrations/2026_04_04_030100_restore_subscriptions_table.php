<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Restore the compact subscription ledger for premium access tracking.
     */
    public function up(): void
    {
        if (Schema::hasTable('subscriptions')) {
            return;
        }

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name')->default('gold');
            $table->string('stripe_id')->nullable();
            $table->string('stripe_status')->default('active');
            $table->string('stripe_price')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'stripe_status'], 'subscriptions_user_status_index');
            $table->index('stripe_id', 'subscriptions_stripe_id_index');
            $table->index('ends_at', 'subscriptions_ends_at_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
