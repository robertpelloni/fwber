<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // Ensure price exists first (fixing schema drift)
            if (!Schema::hasColumn('events', 'price')) {
                $table->decimal('price', 10, 2)->nullable()->after('max_attendees');
            }

            // Add token_cost if it doesn't exist
            if (!Schema::hasColumn('events', 'token_cost')) {
                $table->decimal('token_cost', 8, 2)->nullable()->after('price');
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
