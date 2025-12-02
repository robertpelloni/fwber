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
        Schema::create('geo_spoof_detections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            $table->string('ip_address')->nullable();
            
            // Reported Location
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            
            // IP Location
            $table->decimal('ip_latitude', 10, 8)->nullable();
            $table->decimal('ip_longitude', 11, 8)->nullable();
            
            // Analysis
            $table->integer('distance_km')->nullable();
            $table->integer('velocity_kmh')->nullable();
            $table->integer('suspicion_score')->default(0);
            $table->json('detection_flags')->nullable();
            
            $table->boolean('is_confirmed_spoof')->default(false);
            $table->timestamp('detected_at')->useCurrent();
            
            $table->timestamps();
            
            $table->index(['user_id', 'detected_at']);
            $table->index('suspicion_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('geo_spoof_detections');
    }
};
