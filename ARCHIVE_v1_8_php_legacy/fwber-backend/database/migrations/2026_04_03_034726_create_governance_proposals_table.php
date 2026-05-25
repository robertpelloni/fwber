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
    }

    public function down(): void
    {
        Schema::dropIfExists('governance_proposals');
    }
};
