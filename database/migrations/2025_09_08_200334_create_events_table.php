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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['party', 'club_night', 'themed_event', 'convention', 'workshop', 'social']);
            $table->datetime('starts_at');
            $table->datetime('ends_at');
            $table->datetime('registration_opens_at')->nullable();
            $table->datetime('registration_closes_at')->nullable();
            $table->integer('max_capacity');
            $table->integer('min_capacity')->default(10);
            $table->decimal('base_price', 8, 2)->default(0);
            $table->decimal('couple_price', 8, 2)->nullable();
            $table->decimal('vip_price', 8, 2)->nullable();
            $table->json('age_requirements')->nullable(); // {"min_age": 21, "max_age": null}
            $table->json('gender_requirements')->nullable(); // {"male_max": 50, "female_min": 20, "couple_spots": 10}
            $table->json('dress_code')->nullable();
            $table->json('activities')->nullable(); // ["dancing", "mingling", "bdsm", "couples_play"]
            $table->json('interests')->nullable(); // kink/fetish categories
            $table->text('rules')->nullable();
            $table->boolean('requires_verification')->default(false);
            $table->boolean('requires_membership')->default(false);
            $table->boolean('requires_vaccination')->default(false);
            $table->enum('status', ['draft', 'published', 'cancelled', 'completed'])->default('draft');
            $table->boolean('is_private')->default(false);
            $table->string('invitation_code')->nullable();
            $table->timestamps();
            
            $table->index(['venue_id', 'starts_at']);
            $table->index(['status', 'starts_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
