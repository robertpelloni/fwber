<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('followings', function (Blueprint $table) {
            $table->string('actor_type')->default('Person')->after('actor_uri');
        });

        Schema::table('followers', function (Blueprint $table) {
            $table->string('actor_type')->default('Person')->after('actor_uri');
        });
    }

    public function down(): void
    {
        Schema::table('followings', function (Blueprint $table) {
            $table->dropColumn('actor_type');
        });

        Schema::table('followers', function (Blueprint $table) {
            $table->dropColumn('actor_type');
        });
    }
};
