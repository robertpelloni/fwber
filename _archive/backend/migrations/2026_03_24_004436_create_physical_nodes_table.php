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
        Schema::create('physical_nodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_profile_id')->constrained('merchant_profiles')->onDelete('cascade');
            $table->uuid('node_uuid')->unique();
            $table->string('node_type')->default('pulse_board'); // pulse_board, proximity_sensor, kiosk
            $table->string('name');
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->boolean('is_online')->default(false);
            $table->timestamp('last_heartbeat_at')->nullable();
            $table->json('config')->nullable(); // UI themes, radius settings, etc.
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('physical_nodes');
    }
};
