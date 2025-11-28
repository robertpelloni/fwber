<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_physical_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->integer('height_cm')->nullable();
            $table->string('body_type')->nullable();
            $table->string('hair_color')->nullable();
            $table->string('eye_color')->nullable();
            $table->string('skin_tone')->nullable();
            $table->string('ethnicity')->nullable();
            $table->string('facial_hair')->nullable();
            $table->boolean('tattoos')->default(false);
            $table->boolean('piercings')->default(false);
            $table->string('dominant_hand')->nullable(); // left, right, ambi
            $table->string('fitness_level')->nullable(); // low, average, fit, athletic
            $table->string('clothing_style')->nullable();
            // Avatar generation
            $table->text('avatar_prompt')->nullable();
            $table->string('avatar_image_url')->nullable();
            $table->string('avatar_status')->default('none'); // none|requested|generating|ready|failed
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_physical_profiles');
    }
};
