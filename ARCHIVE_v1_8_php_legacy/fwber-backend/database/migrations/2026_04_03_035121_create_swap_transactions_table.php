<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('swap_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('source_asset')->default('FWB_TOKEN');
            $table->string('target_asset'); // SOL, ETH, USDC, PEER_TOKEN
            $table->decimal('source_amount', 12, 4);
            $table->decimal('target_amount', 12, 4)->nullable();
            $table->string('destination_address')->nullable(); // On-chain address or peer actor URI
            $table->string('status')->default('pending'); // pending, completed, failed
            $table->string('tx_hash')->nullable(); // External chain transaction hash
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('swap_transactions');
    }
};
