<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Block & Report System Migration
 * 
 * AI Model: OpenRouter (via Zen MCP) - Simulated
 * Phase: 4 - User Safety Features (Database Schema)
 * Purpose: Enable users to block and report other users for safety
 * 
 * Created: 2025-10-18
 * Part of: User Safety initiative for dating platform
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Blocked Users Table
        Schema::create('blocked_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blocker_user_id')
                ->constrained('users')
                ->onDelete('cascade');
            $table->foreignId('blocked_user_id')
                ->constrained('users')
                ->onDelete('cascade');
            $table->enum('reason', [
                'not_interested',
                'inappropriate_behavior',
                'harassment',
                'spam',
                'fake_profile',
                'other'
            ])->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Indexes for performance
            $table->index('blocker_user_id');
            $table->index('blocked_user_id');
            $table->index('created_at');
            
            // Prevent duplicate blocks
            $table->unique(['blocker_user_id', 'blocked_user_id'], 'unique_block');
        });
        
        // Reports Table
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporter_user_id')
                ->constrained('users')
                ->onDelete('cascade');
            $table->foreignId('reported_user_id')
                ->constrained('users')
                ->onDelete('cascade');
            $table->enum('report_type', [
                'harassment',
                'hate_speech',
                'fake_profile',
                'inappropriate_content',
                'inappropriate_photos',
                'underage',
                'spam',
                'scam',
                'violence_threat',
                'other'
            ]);
            $table->text('description');
            $table->json('evidence')->nullable(); // Screenshots, message IDs, etc.
            $table->enum('status', [
                'pending',
                'under_review',
                'action_taken',
                'dismissed',
                'escalated'
            ])->default('pending');
            $table->foreignId('reviewed_by')->nullable()
                ->constrained('users')
                ->onDelete('set null');
            $table->text('moderator_notes')->nullable();
            $table->enum('action_taken', [
                'none',
                'warning_sent',
                'temporary_suspension',
                'permanent_ban',
                'content_removed',
                'under_investigation'
            ])->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            
            // Indexes for admin panel queries
            $table->index('reporter_user_id');
            $table->index('reported_user_id');
            $table->index('status');
            $table->index('report_type');
            $table->index('created_at');
            $table->index(['status', 'created_at']);
        });
        
        // User Suspensions Table
        Schema::create('user_suspensions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');
            $table->foreignId('suspended_by')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');
            $table->enum('suspension_type', [
                'temporary',
                'permanent',
                'pending_review'
            ]);
            $table->timestamp('suspended_at');
            $table->timestamp('expires_at')->nullable();
            $table->text('reason');
            $table->foreignId('related_report_id')->nullable()
                ->constrained('reports')
                ->onDelete('set null');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Indexes
            $table->index('user_id');
            $table->index(['user_id', 'is_active']);
            $table->index('expires_at');
        });
        
        // Report Statistics (for analytics)
        Schema::create('report_statistics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');
            $table->integer('reports_received')->default(0);
            $table->integer('reports_submitted')->default(0);
            $table->integer('blocks_received')->default(0);
            $table->integer('blocks_submitted')->default(0);
            $table->decimal('trust_score', 3, 2)->default(1.00); // 0.00-1.00
            $table->timestamp('last_report_at')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('user_id');
            $table->index('trust_score');
            $table->index('reports_received');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_statistics');
        Schema::dropIfExists('user_suspensions');
        Schema::dropIfExists('reports');
        Schema::dropIfExists('blocked_users');
    }
};

