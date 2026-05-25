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
        Schema::create('promotion_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('promotion_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->enum('type', ['view', 'click', 'redemption']);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('promotion_id')->references('id')->on('promotions')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');

            $table->index(['promotion_id', 'type']);
            $table->index(['created_at']);
        });

        Schema::table('promotions', function (Blueprint $table) {
            $table->unsignedBigInteger('views')->default(0);
            $table->unsignedBigInteger('clicks')->default(0);
            $table->unsignedBigInteger('redemptions')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->dropColumn(['views', 'clicks', 'redemptions']);
        });

        Schema::dropIfExists('promotion_events');
    }
};
