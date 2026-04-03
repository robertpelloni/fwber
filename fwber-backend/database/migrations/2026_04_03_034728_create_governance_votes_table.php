<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('governance_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('governance_proposal_id')->constrained()->onDelete('cascade');
            $table->integer('option_index');
            $table->decimal('token_weight', 12, 4);
            $table->timestamps();

            $table->unique(['user_id', 'governance_proposal_id'], 'user_proposal_unique_vote');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('governance_votes');
    }
};
