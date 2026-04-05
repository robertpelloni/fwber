<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'referrer_id')) {
                $table->unsignedBigInteger('referrer_id')->nullable()->after('referral_code');
                $table->index('referrer_id');
            }
        });

        if (! Schema::hasTable('referral_commissions')) {
            Schema::create('referral_commissions', function (Blueprint $table): void {
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
                $table->decimal('token_amount', 10, 2)->default(0);
                $table->string('source', 64)->default('premium_purchase');
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['beneficiary_user_id', 'level']);
                $table->index(['purchaser_user_id', 'level']);
            });
        }

        if (! Schema::hasTable('video_calls')) {
            Schema::create('video_calls', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('caller_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('receiver_id')->constrained('users')->cascadeOnDelete();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('ended_at')->nullable();
                $table->enum('status', ['initiated', 'connected', 'missed', 'rejected', 'ended'])->default('initiated');
                $table->integer('duration')->nullable();
                $table->timestamps();

                $table->index(['caller_id', 'created_at']);
                $table->index(['receiver_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('video_calls');
        Schema::dropIfExists('referral_commissions');

        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'referrer_id')) {
                $table->dropIndex(['referrer_id']);
                $table->dropColumn('referrer_id');
            }
        });
    }
};
