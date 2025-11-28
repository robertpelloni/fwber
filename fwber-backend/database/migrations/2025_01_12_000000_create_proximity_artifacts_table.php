<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('proximity_artifacts')) {
            Schema::create('proximity_artifacts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->enum('type', ['chat', 'board_post', 'announce']);
                $table->text('content');
                $table->decimal('location_lat', 10, 7)->index();
                $table->decimal('location_lng', 10, 7)->index();
                $table->unsignedInteger('visibility_radius_m')->default(1000);
                $table->enum('moderation_status', ['clean', 'flagged', 'shadow_throttled', 'removed'])->default('clean');
                $table->json('meta')->nullable();
                $table->timestamp('expires_at')->index();
                $table->timestamps();

                $table->index(['type', 'expires_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('proximity_artifacts');
    }
};
