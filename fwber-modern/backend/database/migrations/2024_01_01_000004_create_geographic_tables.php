<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // US ZIP codes table
        Schema::create('zipgeo', function (Blueprint $table) {
            $table->id();
            $table->string('zip5', 5);
            $table->string('city', 250);
            $table->string('state', 250);
            $table->decimal('lat', 10, 8);
            $table->decimal('lon', 11, 8);
            $table->string('county', 250);
            
            $table->index('zip5');
            $table->index(['lat', 'lon']);
        });
        
        // World postal codes table
        Schema::create('zipgeoworld', function (Blueprint $table) {
            $table->id();
            $table->string('iso_country_code', 2);
            $table->string('postal_code', 20);
            $table->string('city', 180);
            $table->string('state', 100);
            $table->string('state_code', 20);
            $table->string('county1', 100);
            $table->string('county2', 20);
            $table->string('community1', 100);
            $table->string('community2', 20);
            $table->decimal('lat', 10, 8);
            $table->decimal('lon', 11, 8);
            $table->integer('accuracy');
            
            $table->index(['iso_country_code', 'postal_code']);
            $table->index(['lat', 'lon']);
        });
        
        // Country codes table
        Schema::create('countrycodes', function (Blueprint $table) {
            $table->id();
            $table->string('iso_country_code', 2)->unique();
            $table->string('country_name', 100);
            $table->string('country_name_short', 50);
            
            $table->index('iso_country_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zipgeo');
        Schema::dropIfExists('zipgeoworld');
        Schema::dropIfExists('countrycodes');
    }
};
