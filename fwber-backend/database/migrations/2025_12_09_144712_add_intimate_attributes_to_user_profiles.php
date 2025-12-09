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
            if (!Schema::hasColumn('user_profiles', 'penis_length_cm')) {
                $table->decimal('penis_length_cm', 5, 2)->nullable();
            }
            if (!Schema::hasColumn('user_profiles', 'penis_girth_cm')) {
                $table->decimal('penis_girth_cm', 5, 2)->nullable();
            }
            if (!Schema::hasColumn('user_profiles', 'sti_status')) {
                $table->json('sti_status')->nullable();
            }
            if (!Schema::hasColumn('user_profiles', 'fetishes')) {
                $table->json('fetishes')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'penis_length_cm',
                'penis_girth_cm',
                'sti_status',
                'fetishes',
            ]);
        });
    }
};
