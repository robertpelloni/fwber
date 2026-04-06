<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchant_inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_profile_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price_tokens', 12, 4); // Price in FWB Tokens
            $table->integer('stock_count')->default(0);
            $table->string('image_url')->nullable();
            $table->boolean('is_available')->default(true);
            $table->timestamps();
        });

        Schema::create('inventory_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('merchant_inventory_id')->constrained()->onDelete('cascade');
            $table->string('redemption_code')->unique();
            $table->timestamp('redeemed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_redemptions');
        Schema::dropIfExists('merchant_inventories');
    }
};
