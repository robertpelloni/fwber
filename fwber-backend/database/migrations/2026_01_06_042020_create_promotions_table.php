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
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained('merchant_profiles')->onDelete('cascade');
            $table->string('title');
            $table->text('description');
            $table->string('promo_code')->nullable();
            $table->string('discount_value'); // e.g., "20% OFF", "BOGO"
            
            // Geolocation for "AR" discovery
            $table->decimal('lat', 10, 8)->index();
            $table->decimal('lng', 11, 8)->index();
            $table->integer('radius')->default(100); // meters
            
            $table->integer('token_cost')->default(0); // Cost to claim/view
            $table->timestamp('starts_at');
            $table->timestamp('expires_at');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
