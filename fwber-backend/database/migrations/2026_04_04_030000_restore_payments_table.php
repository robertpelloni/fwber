<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Restore the minimal payments ledger required for premium billing.
     *
     * We create the table only when absent so partially restored hosts and
     * retrying deploys do not fail.
     */
    public function up(): void
    {
        if (Schema::hasTable('payments')) {
            return;
        }

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('payment_gateway');
            $table->string('transaction_id')->nullable();
            $table->string('status');
            $table->string('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at'], 'payments_user_created_index');
            $table->index('transaction_id', 'payments_transaction_id_index');
            $table->index(['status', 'created_at'], 'payments_status_created_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
