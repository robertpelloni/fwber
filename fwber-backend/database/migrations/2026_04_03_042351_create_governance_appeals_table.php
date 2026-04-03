<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('governance_appeals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('reason'); // User's rationale for the appeal
            $table->string('status')->default('pending'); // pending, proposal_created, rejected
            $table->foreignId('proposal_id')->nullable()->constrained('governance_proposals')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('governance_appeals');
    }
};
