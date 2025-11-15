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
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->enum('visibility', ['public', 'private'])->default('public');
                $table->string('avatar_url')->nullable();
                $table->foreignId('creator_id')->constrained('users')->onDelete('cascade');
                $table->integer('max_members')->default(100);
                $table->json('settings')->nullable(); // For extensibility (e.g., join approval, posting permissions)
                $table->boolean('is_active')->default(true);
            $table->timestamps();
            
                $table->index('visibility');
                $table->index('is_active');
                $table->index('creator_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('groups');
    }
};
