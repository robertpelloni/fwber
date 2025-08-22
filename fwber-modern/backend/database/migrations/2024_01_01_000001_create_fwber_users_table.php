<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fwber_users', function (Blueprint $table) {
            $table->id();
            
            // Basic authentication
            $table->string('email')->unique();
            $table->string('password');
            $table->string('verify_hash')->nullable();
            $table->boolean('verified')->default(false);
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            
            // Profile information
            $table->string('first_name');
            $table->string('last_name');
            $table->date('date_of_birth');
            $table->string('gender'); // male, female, mtf, ftm, cdmtf, cdftm, mf, mm, ff, group
            
            // Physical attributes
            $table->string('body_type')->nullable(); // tiny, slim, average, muscular, curvy, thick, bbw
            $table->string('ethnicity')->nullable(); // white, asian, latino, indian, black, other
            $table->string('hair_color')->nullable(); // light, medium, dark, red, gray, other
            $table->string('hair_length')->nullable(); // bald, short, medium, long
            $table->string('tattoos')->nullable(); // none, some, all_over
            $table->string('overall_looks')->nullable(); // ugly, plain, quirks, average, attractive, hottie, supermodel
            $table->string('intelligence')->nullable(); // slow, bit_slow, average, faster, genius
            $table->string('bedroom_personality')->nullable(); // passive, shy, confident, aggressive
            $table->string('pubic_hair')->nullable(); // shaved, trimmed, average, natural, hairy
            $table->string('penis_size')->nullable(); // tiny, skinny, average, thick, huge
            $table->string('body_hair')->nullable(); // smooth, average, hairy
            $table->string('breast_size')->nullable(); // tiny, small, average, large, huge
            
            // Lifestyle
            $table->boolean('smoke_cigarettes')->default(false);
            $table->boolean('light_drinker')->default(false);
            $table->boolean('heavy_drinker')->default(false);
            $table->boolean('smoke_marijuana')->default(false);
            $table->boolean('psychedelics')->default(false);
            $table->boolean('other_drugs')->default(false);
            
            // Health status
            $table->boolean('have_warts')->default(false);
            $table->boolean('have_herpes')->default(false);
            $table->boolean('have_hepatitis')->default(false);
            $table->boolean('have_other_sti')->default(false);
            $table->boolean('have_hiv')->default(false);
            
            // Relationship status
            $table->boolean('married_they_know')->default(false);
            $table->boolean('married_secret')->default(false);
            $table->boolean('poly')->default(false);
            $table->boolean('disability')->default(false);
            
            // Location
            $table->string('postal_code')->nullable();
            $table->string('country')->nullable();
            $table->string('iso_country_code', 2)->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->decimal('lat', 10, 8)->nullable();
            $table->decimal('lon', 11, 8)->nullable();
            
            // Profile content
            $table->text('public_text')->nullable();
            $table->text('private_text')->nullable();
            $table->string('avatar_url')->nullable();
            
            // Timestamps
            $table->timestamp('date_joined')->useCurrent();
            $table->timestamp('date_last_signed_in')->nullable();
            $table->timestamp('date_last_seen')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['lat', 'lon']);
            $table->index('gender');
            $table->index('verified');
            $table->index('date_last_seen');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fwber_users');
    }
};
