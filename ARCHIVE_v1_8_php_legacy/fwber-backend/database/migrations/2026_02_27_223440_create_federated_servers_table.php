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
        Schema::create('federated_servers', function (Blueprint $table) {
            $table->id();
            $table->string('domain')->unique()->index();
            $table->string('software')->nullable(); // e.g., 'Mastodon', 'Misskey', 'fwber'
            $table->string('version')->nullable();
            $table->boolean('is_blocked')->default(false)->index();
            $table->timestamp('discovered_at')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('federated_servers');
    }
};
