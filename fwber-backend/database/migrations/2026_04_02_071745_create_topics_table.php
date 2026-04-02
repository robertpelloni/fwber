<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('topics', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('label');
            $table->string('description', 280)->nullable();
            $table->string('emoji', 16)->nullable();
            $table->string('category', 50)->default('interest');
            $table->json('aliases')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('topic_user_follows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('topic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('followed_at')->nullable();
            $table->timestamps();

            $table->unique(['topic_id', 'user_id']);
        });

        $now = now();

        DB::table('topics')->insert([
            [
                'slug' => 'music',
                'label' => 'Music',
                'description' => 'Live sets, playlists, dance floors, and shared listening rituals.',
                'emoji' => '🎵',
                'category' => 'culture',
                'aliases' => json_encode(['music', 'live-music', 'dj', 'jazz', 'concerts']),
                'is_featured' => true,
                'sort_order' => 10,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => 'outdoors',
                'label' => 'Outdoors',
                'description' => 'Hiking, camping, trail days, and open-air adventures.',
                'emoji' => '🌲',
                'category' => 'lifestyle',
                'aliases' => json_encode(['outdoors', 'hiking', 'camping', 'nature', 'trail']),
                'is_featured' => true,
                'sort_order' => 20,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => 'fitness',
                'label' => 'Fitness',
                'description' => 'Training partners, movement routines, and recovery culture.',
                'emoji' => '💪',
                'category' => 'wellness',
                'aliases' => json_encode(['fitness', 'gym', 'running', 'cycling', 'workout']),
                'is_featured' => true,
                'sort_order' => 30,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => 'wellness',
                'label' => 'Wellness',
                'description' => 'Yoga, meditation, saunas, spas, and soft reset energy.',
                'emoji' => '🧘',
                'category' => 'wellness',
                'aliases' => json_encode(['wellness', 'yoga', 'meditation', 'saunas', 'spas']),
                'is_featured' => true,
                'sort_order' => 40,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => 'gaming',
                'label' => 'Gaming',
                'description' => 'Console nights, online squads, arcades, and playful chaos.',
                'emoji' => '🎮',
                'category' => 'play',
                'aliases' => json_encode(['gaming', 'video-games', 'arcade', 'esports']),
                'is_featured' => true,
                'sort_order' => 50,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => 'foodie',
                'label' => 'Foodie',
                'description' => 'Dinner clubs, tasting notes, coffee runs, and kitchen chemistry.',
                'emoji' => '🍜',
                'category' => 'culture',
                'aliases' => json_encode(['foodie', 'cooking', 'coffee', 'restaurants', 'brunch']),
                'is_featured' => true,
                'sort_order' => 60,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => 'reading',
                'label' => 'Reading',
                'description' => 'Book clubs, long reads, shared annotations, and quiet obsessions.',
                'emoji' => '📚',
                'category' => 'culture',
                'aliases' => json_encode(['reading', 'books', 'writing', 'literature']),
                'is_featured' => false,
                'sort_order' => 70,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => 'art-design',
                'label' => 'Art & Design',
                'description' => 'Gallery crawls, sketchbooks, visual taste, and making things.',
                'emoji' => '🎨',
                'category' => 'culture',
                'aliases' => json_encode(['art', 'design', 'photography', 'fashion']),
                'is_featured' => false,
                'sort_order' => 80,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => 'nightlife',
                'label' => 'Nightlife',
                'description' => 'Late nights, warehouse scenes, dancing, and after-hours energy.',
                'emoji' => '🌃',
                'category' => 'scene',
                'aliases' => json_encode(['nightlife', 'dancing', 'clubs', 'warehouse', 'afterparty']),
                'is_featured' => true,
                'sort_order' => 90,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => 'travel',
                'label' => 'Travel',
                'description' => 'Weekend escapes, nomad plans, city guides, and passport chemistry.',
                'emoji' => '✈️',
                'category' => 'lifestyle',
                'aliases' => json_encode(['travel', 'traveling', 'roadtrip', 'vacation']),
                'is_featured' => false,
                'sort_order' => 100,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => 'tech',
                'label' => 'Tech',
                'description' => 'Builders, gadgets, late-night side projects, and digital curiosity.',
                'emoji' => '💻',
                'category' => 'interest',
                'aliases' => json_encode(['tech', 'technology', 'coding', 'startups']),
                'is_featured' => false,
                'sort_order' => 110,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => 'community',
                'label' => 'Community',
                'description' => 'Volunteering, mutual support, and people who like showing up.',
                'emoji' => '🤝',
                'category' => 'social',
                'aliases' => json_encode(['community', 'volunteering', 'activism', 'mutual-aid']),
                'is_featured' => false,
                'sort_order' => 120,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('topic_user_follows');
        Schema::dropIfExists('topics');
    }
};
