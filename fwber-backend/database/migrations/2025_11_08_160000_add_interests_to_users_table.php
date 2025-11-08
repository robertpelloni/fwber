<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Use TEXT for SQLite compatibility; cast to array in model
            if (!Schema::hasColumn('users', 'interests')) {
                $table->text('interests')->nullable()->after('remember_token');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'interests')) {
                $table->dropColumn('interests');
            }
        });
    }
};
