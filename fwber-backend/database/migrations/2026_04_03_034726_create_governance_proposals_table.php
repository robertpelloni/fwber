<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('governance_proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creator_id')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->text('description');
            $table->string('category')->default('policy'); // mod, policy, tech, treasury
            $table->json('options'); // ['Yes', 'No', 'Abstain']
            $table->decimal('min_tokens_required', 12, 4)->default(10.0000);
            $table->timestamp('starts_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();
            $table->string('status')->default('active'); // active, passed, failed, archived
            $table->timestamps();
        });

        Schema::create('governance_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('governance_proposal_id')->constrained()->onDelete('cascade');
            $table->integer('option_index');
            $table->decimal('token_weight', 12, 4); // The weight is the number of tokens the user held/committed at time of vote
            $table->timestamps();

            $table->unique(['user_id', 'governance_proposal_id'], 'user_proposal_unique_vote');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('governance_votes');
        Schema::dropIfExists('governance_proposals');
    }
};
