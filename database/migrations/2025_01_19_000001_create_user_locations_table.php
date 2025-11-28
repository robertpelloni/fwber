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
        if (!Schema::hasTable('user_locations')) {
            Schema::create('user_locations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->decimal('latitude', 10, 8)->comment('Latitude coordinate');
                $table->decimal('longitude', 11, 8)->comment('Longitude coordinate');
                $table->decimal('accuracy', 8, 2)->nullable()->comment('Location accuracy in meters');
                $table->decimal('heading', 5, 2)->nullable()->comment('Direction of travel in degrees');
                $table->decimal('speed', 8, 2)->nullable()->comment('Speed in meters per second');
                $table->decimal('altitude', 8, 2)->nullable()->comment('Altitude in meters');
                $table->boolean('is_active')->default(true)->comment('Whether location sharing is active');
                $table->enum('privacy_level', ['public', 'friends', 'private'])->default('friends')->comment('Location privacy level');
                $table->timestamp('last_updated')->useCurrent()->comment('Last location update timestamp');
                $table->timestamps();

                // Indexes for performance
                $table->index(['user_id', 'is_active']);
                $table->index(['latitude', 'longitude']);
                $table->index('privacy_level');
                $table->index('last_updated');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_locations');
    }
};
