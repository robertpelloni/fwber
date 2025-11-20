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
        Schema::create('shadow_throttles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('reason', ['spam', 'flagged_content', 'geo_spoof', 'rapid_posting', 'manual']);
            $table->integer('severity')->default(1); // 1-5 scale
            $table->decimal('visibility_reduction', 5, 2)->default(0.50); // 0.0 = invisible, 1.0 = full visibility
            $table->timestamp('started_at');
            $table->timestamp('expires_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null'); // Moderator who applied it
            $table->timestamps();

            $table->index(['user_id', 'expires_at']);
            $table->index(['severity', 'started_at']);
        });

        Schema::create('geo_spoof_detections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('ip_address', 45);
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->decimal('ip_latitude', 10, 8)->nullable();
            $table->decimal('ip_longitude', 11, 8)->nullable();
            $table->integer('distance_km')->nullable(); // Distance between claimed location and IP geolocation
            $table->integer('velocity_kmh')->nullable(); // Speed required since last location update
            $table->integer('suspicion_score')->default(0); // 0-100 scale
            $table->json('detection_flags')->nullable(); // Array of specific detection triggers
            $table->boolean('is_confirmed_spoof')->default(false);
            $table->timestamp('detected_at');
            $table->timestamps();

            $table->index(['user_id', 'detected_at']);
            $table->index(['suspicion_score', 'is_confirmed_spoof']);
            $table->index('ip_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('geo_spoof_detections');
        Schema::dropIfExists('shadow_throttles');
    }
};
