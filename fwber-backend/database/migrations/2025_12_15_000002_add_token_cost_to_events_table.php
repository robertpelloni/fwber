<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // Ensure max_attendees exists (fixing schema drift)
            if (!Schema::hasColumn('events', 'max_attendees')) {
                $table->integer('max_attendees')->nullable();
            }

            // Ensure price exists first (fixing schema drift)
            if (!Schema::hasColumn('events', 'price')) {
                if (Schema::hasColumn('events', 'max_attendees')) {
                    $table->decimal('price', 10, 2)->nullable()->after('max_attendees');
                } else {
                    $table->decimal('price', 10, 2)->nullable();
                }
            }

            // Add token_cost if it doesn't exist
            if (!Schema::hasColumn('events', 'token_cost')) {
                if (Schema::hasColumn('events', 'price')) {
                    $table->decimal('token_cost', 8, 2)->nullable()->after('price');
                } else {
                    $table->decimal('token_cost', 8, 2)->nullable();
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            if (Schema::hasColumn('events', 'token_cost')) {
                $table->dropColumn('token_cost');
            }
            // We don't drop price here as it might be needed or was added to fix drift
        });
    }
};
