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
        Schema::table('groups', function (Blueprint $table) {
            $table->json('tags')->nullable()->after('description');
            $table->string('category')->nullable()->after('tags');
            $table->boolean('matching_enabled')->default(false)->after('is_active');
            $table->decimal('location_lat', 10, 8)->nullable()->after('matching_enabled');
            $table->decimal('location_lon', 11, 8)->nullable()->after('location_lat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn([
                'tags', 
                'category', 
                'matching_enabled', 
                'location_lat', 
                'location_lon'
            ]);
        });
    }
};
