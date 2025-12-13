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
            $table->boolean('is_travel_mode')->default(false);
            $table->decimal('travel_latitude', 10, 8)->nullable();
            $table->decimal('travel_longitude', 11, 8)->nullable();
            $table->string('travel_location_name')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'is_travel_mode',
                'travel_latitude',
                'travel_longitude',
                'travel_location_name'
            ]);
        });
    }
};
