<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('user_profiles', 'journal_visibility_default')) {
                $table->string('journal_visibility_default', 20)->default('friends')->after('voice_intro_url');
            }

            if (! Schema::hasColumn('user_profiles', 'journal_circle_group_id')) {
                $table->foreignId('journal_circle_group_id')
                    ->nullable()
                    ->after('journal_visibility_default')
                    ->constrained('groups')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('user_profiles', 'journal_circle_group_id')) {
                $table->dropConstrainedForeignId('journal_circle_group_id');
            }

            if (Schema::hasColumn('user_profiles', 'journal_visibility_default')) {
                $table->dropColumn('journal_visibility_default');
            }
        });
    }
};
