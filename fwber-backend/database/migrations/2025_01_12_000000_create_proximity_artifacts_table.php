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
        Schema::create('proximity_artifacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type'); // encounter, location_ping, etc.
            $table->text('content')->nullable();
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->string('geohash')->index();
            $table->integer('visibility_radius_m')->default(1000);
            $table->timestamp('expires_at')->nullable();
            $table->json('metadata')->nullable();
            
            // Safety/Moderation
            $table->boolean('is_flagged')->default(false);
            $table->string('flag_reason')->nullable();
            $table->softDeletes();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proximity_artifacts');
    }
};
