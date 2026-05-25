<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('governance_proposals', function (Blueprint $table) {
            $table->json('execution_payload')->nullable()->after('options');
            $table->timestamp('executed_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('governance_proposals', function (Blueprint $table) {
            $table->dropColumn(['execution_payload', 'executed_at']);
        });
    }
};
