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
        Schema::table('event_attendees', function (Blueprint $table) {
            $table->boolean('paid')->default(false);
            $table->string('payment_method')->nullable(); // stripe, token
            $table->string('transaction_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_attendees', function (Blueprint $table) {
            $table->dropColumn(['paid', 'payment_method', 'transaction_id']);
        });
    }
};
