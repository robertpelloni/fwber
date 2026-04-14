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
        Schema::table('match_assists', function (Blueprint $table) {
            $table->foreignId('match_bounty_id')->nullable()->after('id')->constrained('match_bounties')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('match_assists', function (Blueprint $table) {
            $table->dropForeign(['match_bounty_id']);
            $table->dropColumn('match_bounty_id');
        });
    }
};
