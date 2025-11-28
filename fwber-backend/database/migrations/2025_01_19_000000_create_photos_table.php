<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create Photos Table Migration
 * 
 * AI Model: Claude 4.5 - Multi-AI Orchestration
 * Phase: 4A - Laravel Photo Upload System
 * Purpose: Database schema for user photo management
 * 
 * Created: 2025-01-19
 * Multi-AI: Serena analysis + Chroma knowledge + Sequential thinking
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('photos')) {
            Schema::create('photos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('filename');
                $table->string('original_filename');
                $table->string('file_path');
                $table->string('thumbnail_path')->nullable();
                $table->string('mime_type');
                $table->integer('file_size');
                $table->integer('width')->nullable();
                $table->integer('height')->nullable();
                $table->boolean('is_primary')->default(false);
                $table->boolean('is_private')->default(false);
                $table->integer('sort_order')->default(0);
                $table->json('metadata')->nullable();
                $table->timestamps();
                
                // Indexes for performance
                $table->index(['user_id', 'is_primary']);
                $table->index(['user_id', 'sort_order']);
                $table->index(['user_id', 'is_private']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('photos');
    }
};
