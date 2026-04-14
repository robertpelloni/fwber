<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referral_commissions', function (Blueprint $table) {
            $table->id();
            $table->string('commission_key')->unique();
            $table->foreignId('purchaser_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('beneficiary_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete();
            $table->unsignedTinyInteger('level');
            $table->decimal('cash_amount', 10, 2)->default(0);
            $table->string('cash_currency', 3)->default('USD');
            $table->string('cash_status', 32)->default('pending');
            $table->decimal('token_amount', 16, 4)->default(0);
            $table->string('source', 64)->default('premium_purchase');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['beneficiary_user_id', 'level']);
            $table->index(['purchaser_user_id', 'level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_commissions');
    }
};
