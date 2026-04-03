<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('federated_actor_reputations', function (Blueprint $table) {
            $table->id();
            $table->string('actor_uri')->unique()->index();
            $table->integer('vouch_count')->default(0);
            $table->timestamp('member_since')->nullable();
            $table->json('reputation_metadata')->nullable(); // Store extra flags like 'verified_real' etc
            $table->timestamp('last_synced_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('federated_actor_reputations');
    }
};
