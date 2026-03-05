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
        Schema::table('users', function (Blueprint $table) {
            $table->string('decoy_password')->nullable()->after('password');
            $table->unsignedBigInteger('decoy_user_id')->nullable()->after('decoy_password');
            $table->boolean('is_decoy')->default(false)->after('decoy_user_id');

            $table->foreign('decoy_user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['decoy_user_id']);
            $table->dropColumn(['decoy_password', 'decoy_user_id', 'is_decoy']);
        });
    }
};
