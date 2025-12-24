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
        // Fix missing photo_unlocks table
        if (!Schema::hasTable('photo_unlocks')) {
            Schema::create('photo_unlocks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('photo_id')->constrained()->onDelete('cascade');
                $table->decimal('cost', 8, 2);
                $table->timestamp('unlocked_at');
                $table->timestamps();

                $table->unique(['user_id', 'photo_id']);
            });
        }

        // Fix missing unlock_price column
        if (Schema::hasTable('photos') && !Schema::hasColumn('photos', 'unlock_price')) {
            Schema::table('photos', function (Blueprint $table) {
                $table->decimal('unlock_price', 8, 2)->nullable()->after('is_private');
            });
        }
        
        // Fix missing content_unlocks table (from 2025_12_23_174829)
        if (!Schema::hasTable('content_unlocks')) {
             Schema::create('content_unlocks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('content_type'); // 'match_insight', 'profile_reveal', etc
                $table->string('content_id');   // ID of the content (could be string or int)
                $table->decimal('cost', 10, 2)->default(0);
                $table->timestamps();

                $table->index(['user_id', 'content_type', 'content_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // We don't want to drop these in a fix migration as they might be managed by the original migrations
    }
};
