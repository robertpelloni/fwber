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
            $table->string('merchant_secret')->nullable()->unique()->after('wallet_address');
            $table->string('merchant_name')->nullable()->after('merchant_secret');
        });

        Schema::create('merchant_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('merchant_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('payer_id')->nullable()->constrained('users')->onDelete('set null');
            $table->decimal('amount', 16, 4);
            $table->string('status')->default('pending'); // pending, paid, cancelled
            $table->string('description')->nullable();
            $table->string('redirect_url')->nullable();
            $table->string('webhook_url')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('merchant_payments');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['merchant_secret', 'merchant_name']);
        });
    }
};
