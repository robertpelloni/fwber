<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Restore the compact merchant + marketplace schema.
     *
     * We create each table only if missing so deploy retries and partially
     * restored databases do not fail. This mirrors the safe phased restoration
     * strategy already used for AI and premium/billing.
     */
    public function up(): void
    {
        if (! Schema::hasTable('merchant_profiles')) {
            Schema::create('merchant_profiles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
                $table->string('business_name');
                $table->text('description')->nullable();
                $table->string('category')->index();
                $table->string('address')->nullable();
                $table->string('location_name')->nullable();
                $table->decimal('latitude', 10, 8)->nullable();
                $table->decimal('longitude', 11, 8)->nullable();
                $table->string('verification_status')->default('pending');
                $table->timestamps();

                $table->index(['latitude', 'longitude'], 'merchant_profiles_lat_lng_index');
            });
        }

        if (! Schema::hasTable('merchant_inventories')) {
            Schema::create('merchant_inventories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('merchant_profile_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->text('description')->nullable();
                $table->decimal('price_usd', 10, 2);
                $table->unsignedInteger('stock_count')->default(0);
                $table->string('image_url')->nullable();
                $table->boolean('is_available')->default(true);
                $table->timestamps();

                $table->index(['merchant_profile_id', 'is_available'], 'merchant_inventory_profile_available_index');
            });
        }

        if (! Schema::hasTable('merchant_payments')) {
            Schema::create('merchant_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('merchant_profile_id')->constrained()->cascadeOnDelete();
                $table->foreignId('merchant_inventory_id')->nullable()->constrained('merchant_inventories')->nullOnDelete();
                $table->foreignId('payer_id')->nullable()->constrained('users')->nullOnDelete();
                $table->decimal('amount', 10, 2);
                $table->string('currency', 3)->default('USD');
                $table->string('payment_gateway');
                $table->string('transaction_id')->nullable();
                $table->string('status')->default('pending');
                $table->string('description')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();

                $table->index(['merchant_profile_id', 'status'], 'merchant_payments_profile_status_index');
                $table->index('transaction_id', 'merchant_payments_transaction_index');
            });
        }

        if (! Schema::hasTable('inventory_redemptions')) {
            Schema::create('inventory_redemptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('merchant_inventory_id')->constrained('merchant_inventories')->cascadeOnDelete();
                $table->foreignId('merchant_payment_id')->nullable()->constrained('merchant_payments')->nullOnDelete();
                $table->string('redemption_code')->unique();
                $table->timestamp('redeemed_at')->nullable();
                $table->timestamps();

                $table->index(['merchant_inventory_id', 'redeemed_at'], 'inventory_redemptions_inventory_redeemed_index');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_redemptions');
        Schema::dropIfExists('merchant_payments');
        Schema::dropIfExists('merchant_inventories');
        Schema::dropIfExists('merchant_profiles');
    }
};
