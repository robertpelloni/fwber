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
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('display_name')->nullable();
            $table->text('bio')->nullable();
            $table->date('birthdate')->nullable();
            $table->string('gender')->nullable();
            $table->string('pronouns')->nullable();
            $table->string('sexual_orientation')->nullable();
            $table->string('relationship_status')->nullable();
            $table->string('relationship_style')->nullable();
            
            // Location
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('location_name')->nullable();
            $table->boolean('is_travel_mode')->default(false);
            $table->decimal('travel_latitude', 10, 8)->nullable();
            $table->decimal('travel_longitude', 11, 8)->nullable();
            $table->string('travel_location_name')->nullable();
            
            // Physical & Work
            $table->integer('height_cm')->nullable();
            $table->string('body_type')->nullable();
            $table->string('hair_color')->nullable();
            $table->string('eye_color')->nullable();
            $table->string('ethnicity')->nullable();
            $table->string('occupation')->nullable();
            $table->string('education')->nullable();
            
            // Lifestyle
            $table->string('smoking_status')->nullable();
            $table->string('drinking_status')->nullable();
            $table->string('cannabis_status')->nullable();
            $table->string('zodiac_sign')->nullable();
            $table->boolean('has_children')->default(false);
            $table->boolean('wants_children')->default(false);
            $table->boolean('has_pets')->default(false);
            
            // Preferences & Identity
            $table->json('interests')->nullable();
            $table->json('looking_for')->nullable();
            $table->json('interested_in')->nullable();
            $table->json('preferences')->nullable();
            $table->string('preferred_language')->nullable();
            $table->string('personality_type')->nullable();
            
            // Safety & Verification
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_id_verified')->default(false);
            $table->timestamp('id_verified_at')->nullable();
            $table->string('zk_id_issuer')->nullable();
            $table->string('verification_photo_path')->nullable();
            $table->boolean('is_incognito')->default(false);
            $table->boolean('is_confessional_mode')->default(false);
            $table->string('voice_intro_url')->nullable();
            
            // Intimate
            $table->json('sti_status')->nullable();
            $table->json('fetishes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
    }
};
