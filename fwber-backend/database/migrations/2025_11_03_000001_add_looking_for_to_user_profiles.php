<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_profiles', function (Blueprint $table): void {
            if (!Schema::hasColumn('user_profiles', 'looking_for')) {
                // Use JSON where supported; SQLite maps this to TEXT
                $table->json('looking_for')->nullable()->after('relationship_style');
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table): void {
            if (Schema::hasColumn('user_profiles', 'looking_for')) {
                $table->dropColumn('looking_for');
            }
        });
    }
};
