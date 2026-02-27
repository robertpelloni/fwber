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
        Schema::create('zk_proximity_proofs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('target_entity_type'); // e.g., 'venue', 'user', 'event'
            $table->unsignedBigInteger('target_entity_id');
            $table->text('proof_hash'); // Store the ZK payload hash
            $table->boolean('is_verified')->default(false);
            $table->timestamps();
            
            // Index for fast spatial/entity lookups
            $table->index(['target_entity_type', 'target_entity_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zk_proximity_proofs');
    }
};
