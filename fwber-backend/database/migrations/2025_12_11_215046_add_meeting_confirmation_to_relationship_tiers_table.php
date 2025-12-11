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
        Schema::table('relationship_tiers', function (Blueprint $table) {
            $table->timestamp('user1_confirmed_meeting_at')->nullable()->after('met_in_person_at');
            $table->timestamp('user2_confirmed_meeting_at')->nullable()->after('user1_confirmed_meeting_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('relationship_tiers', function (Blueprint $table) {
            $table->dropColumn(['user1_confirmed_meeting_at', 'user2_confirmed_meeting_at']);
        });
    }
};
