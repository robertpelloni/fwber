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
        if (!Schema::hasTable('bulletin_boards')) {
            Schema::create('bulletin_boards', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                
                // Location
                $table->decimal('center_lat', 10, 8);
                $table->decimal('center_lng', 11, 8);
                $table->integer('radius_meters')->default(5000);
                $table->string('geohash')->nullable()->index();
                
                // Status
                $table->boolean('is_active')->default(true);
                
                // Stats
                $table->integer('message_count')->default(0);
                $table->integer('active_users')->default(0);
                $table->timestamp('last_activity_at')->nullable();
                
                $table->timestamps();
                
                $table->index(['center_lat', 'center_lng']);
            });
        }

        if (!Schema::hasTable('bulletin_messages')) {
            Schema::create('bulletin_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('bulletin_board_id')->constrained()->onDelete('cascade');
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                
                $table->text('content');
                $table->boolean('is_moderated')->default(false);
                
                $table->timestamps();
                
                $table->index(['bulletin_board_id', 'created_at']);
            });
        }

        if (!Schema::hasTable('bulletin_board_users')) {
            Schema::create('bulletin_board_users', function (Blueprint $table) {
                $table->id();
                $table->foreignId('bulletin_board_id')->constrained()->onDelete('cascade');
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                
                $table->timestamp('last_seen_at')->useCurrent();
                $table->boolean('is_online')->default(true);
                
                $table->timestamps();
                
                $table->unique(['bulletin_board_id', 'user_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bulletin_board_users');
        Schema::dropIfExists('bulletin_messages');
        Schema::dropIfExists('bulletin_boards');
    }
};
