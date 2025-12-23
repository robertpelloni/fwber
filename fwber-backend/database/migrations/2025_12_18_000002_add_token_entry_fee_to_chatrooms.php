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
        Schema::table('chatrooms', function (Blueprint $table) {
            $table->decimal('token_entry_fee', 16, 4)->default(0)->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chatrooms', function (Blueprint $table) {
            $table->dropColumn('token_entry_fee');
        });
    }
};
