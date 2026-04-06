<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('relationship_links')) {
            Schema::create('relationship_links', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('related_user_id')->constrained('users')->cascadeOnDelete();
                $table->string('relationship_type', 20)->default('dating');
                $table->string('visibility', 20)->default('friends');
                $table->string('note', 280)->nullable();
                $table->timestamp('requested_at')->nullable();
                $table->timestamp('confirmed_at')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'related_user_id']);
                $table->index(['user_id', 'confirmed_at']);
                $table->index(['related_user_id', 'confirmed_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('relationship_links');
    }
};
