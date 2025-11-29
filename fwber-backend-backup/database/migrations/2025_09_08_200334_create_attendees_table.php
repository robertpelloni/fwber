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
        Schema::create('attendees', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('display_name')->nullable();
            $table->date('birth_date');
            $table->enum('gender', ['male', 'female', 'mtf', 'ftm', 'non_binary', 'couple_mf', 'couple_mm', 'couple_ff', 'group']);
            $table->enum('orientation', ['straight', 'gay', 'bisexual', 'pansexual', 'curious']);
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->default('US');
            $table->string('zip_code')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->json('physical_attributes')->nullable(); // height, weight, body_type, etc
            $table->json('interests')->nullable(); // kink/fetish preferences
            $table->json('experience_level')->nullable(); // beginner, intermediate, expert
            $table->text('bio')->nullable();
            $table->json('looking_for')->nullable(); // what they want from events
            $table->boolean('is_verified')->default(false);
            $table->string('verification_method')->nullable(); // phone, photo, etc
            $table->boolean('is_premium')->default(false);
            $table->timestamp('premium_expires_at')->nullable();
            $table->json('privacy_settings')->nullable();
            $table->string('avatar_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_active_at')->nullable();
            $table->timestamps();
            
            $table->index(['city', 'state']);
            $table->index(['gender', 'orientation']);
            $table->index(['is_verified', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendees');
    }
};
