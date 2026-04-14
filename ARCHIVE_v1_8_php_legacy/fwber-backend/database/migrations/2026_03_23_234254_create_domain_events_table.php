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
        Schema::create('domain_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('aggregate_uuid')->index(); // The ID of the entity (e.g., user_id)
            $table->string('aggregate_type')->index(); // e.g., 'UserLocation', 'Match'
            $table->unsignedInteger('version'); // For optimistic concurrency control
            $table->string('event_type'); // e.g., 'App\Events\UserLocationUpdated'
            $table->json('payload'); // The actual event data
            $table->json('metadata')->nullable(); // IP address, user agent, etc.
            $table->timestamp('recorded_at')->useCurrent()->index();

            // Ensure we don't have duplicate events for the same aggregate version
            $table->unique(['aggregate_uuid', 'aggregate_type', 'version']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domain_events');
    }
};
