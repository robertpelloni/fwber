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
        Schema::table('users', function (Blueprint $table) {
            $table->string('referral_code')->unique()->nullable()->after('email');
            $table->unsignedBigInteger('referrer_id')->nullable()->after('referral_code');
            $table->string('wallet_address')->nullable()->after('referrer_id');
            $table->decimal('token_balance', 16, 4)->default(0)->after('wallet_address');
            
            $table->foreign('referrer_id')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('token_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 16, 4);
            $table->string('type'); // signup_bonus, referral_bonus, early_adopter_bonus
            $table->string('description')->nullable();
            $table->json('metadata')->nullable(); // For storing related user_id, etc.
            $table->timestamps();
            
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('token_transactions');
        
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['referrer_id']);
            $table->dropColumn(['referral_code', 'referrer_id', 'wallet_address', 'token_balance']);
        });
    }
};
