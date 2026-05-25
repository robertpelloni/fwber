<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('federated_instances', function (Blueprint $table) {
            $table->id();
            $table->string('domain')->unique();
            $table->string('actor_uri')->nullable(); // The instance-level actor
            $table->string('software')->default('fwber');
            $table->string('status')->default('active'); // active, suspended, dead
            $table->boolean('relay_enabled')->default(true);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('federated_instances');
    }
};
