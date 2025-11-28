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
        Schema::table('matches', function (Blueprint $table) {
            // Add match_score column if it doesn't exist
            if (!Schema::hasColumn('matches', 'match_score')) {
                $table->integer('match_score')->default(0)->after('user2_id');
            }
            
            // Add status column if it doesn't exist
            if (!Schema::hasColumn('matches', 'status')) {
                $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending')->after('match_score');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            if (Schema::hasColumn('matches', 'match_score')) {
                $table->dropColumn('match_score');
            }
            
            if (Schema::hasColumn('matches', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
