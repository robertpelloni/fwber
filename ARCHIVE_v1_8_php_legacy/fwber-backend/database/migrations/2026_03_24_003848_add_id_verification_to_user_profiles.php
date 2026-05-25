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
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->boolean('is_id_verified')->default(false)->after('is_verified');
            $table->string('zk_id_issuer')->nullable()->after('is_id_verified');
            $table->timestamp('id_verified_at')->nullable()->after('zk_id_issuer');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropColumn(['is_id_verified', 'zk_id_issuer', 'id_verified_at']);
        });
    }
};
