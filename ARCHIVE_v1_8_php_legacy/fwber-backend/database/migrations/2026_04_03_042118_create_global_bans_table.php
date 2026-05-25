<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('global_bans', function (Blueprint $table) {
            $table->id();
            $table->string('bannable_identifier')->unique()->index(); // User ID or Actor URI
            $table->string('type')->default('user'); // user, actor, domain
            $table->string('reason')->nullable();
            $table->foreignId('proposal_id')->nullable()->constrained('governance_proposals')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('global_bans');
    }
};
