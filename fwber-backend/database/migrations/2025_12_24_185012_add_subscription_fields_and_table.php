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
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->integer('subscription_price')->nullable()->after('is_incognito'); // Monthly cost in FWB
        });

        Schema::create('creator_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Subscriber
            $table->foreignId('creator_id')->constrained('users')->onDelete('cascade'); // Creator
            $table->timestamp('expires_at');
            $table->integer('cost'); // Cost paid
            $table->timestamps();

            $table->index(['user_id', 'creator_id']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('creator_subscriptions');
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropColumn('subscription_price');
        });
    }
};
