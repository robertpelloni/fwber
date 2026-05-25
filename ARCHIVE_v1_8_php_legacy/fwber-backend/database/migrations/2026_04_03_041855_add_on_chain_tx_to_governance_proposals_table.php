<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('governance_proposals', function (Blueprint $table) {
            $table->string('on_chain_tx')->nullable()->after('merkle_root');
        });
    }

    public function down(): void
    {
        Schema::table('governance_proposals', function (Blueprint $table) {
            $table->dropColumn('on_chain_tx');
        });
    }
};
