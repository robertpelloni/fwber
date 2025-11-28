<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('proximity_artifacts', function (Blueprint $table) {
            if (!Schema::hasColumn('proximity_artifacts', 'is_flagged')) {
                $table->boolean('is_flagged')->default(false)->index();
            }
            if (!Schema::hasColumn('proximity_artifacts', 'flag_count')) {
                $table->unsignedInteger('flag_count')->default(0);
            }
            if (!Schema::hasColumn('proximity_artifacts', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('proximity_artifacts', function (Blueprint $table) {
            if (Schema::hasColumn('proximity_artifacts', 'is_flagged')) {
                $table->dropColumn('is_flagged');
            }
            if (Schema::hasColumn('proximity_artifacts', 'flag_count')) {
                $table->dropColumn('flag_count');
            }
            if (Schema::hasColumn('proximity_artifacts', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
