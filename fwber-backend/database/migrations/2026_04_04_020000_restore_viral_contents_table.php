<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Restore the lightweight AI share table used by roast/hype/fortune flows.
     *
     * The table is created only when missing so the migration is safe on hosts
     * where an older copy of the feature already exists.
     */
    public function up(): void
    {
        if (Schema::hasTable('viral_contents')) {
            return;
        }

        Schema::create('viral_contents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type');
            $table->json('content');
            $table->unsignedInteger('views')->default(0);
            $table->boolean('reward_claimed')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('viral_contents');
    }
};
