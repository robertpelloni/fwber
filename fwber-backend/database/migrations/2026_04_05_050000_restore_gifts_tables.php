<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('gifts')) {
            Schema::create('gifts', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('description')->nullable();
                $table->integer('cost');
                $table->string('icon_url');
                $table->string('category')->default('general');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('user_gifts')) {
            Schema::create('user_gifts', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('receiver_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('gift_id')->constrained('gifts')->cascadeOnDelete();
                $table->text('message')->nullable();
                $table->integer('cost_at_time');
                $table->timestamps();

                $table->index(['receiver_id', 'created_at']);
            });
        }

        $gifts = [
            ['name' => 'Coffee', 'description' => "Let's grab a coffee sometime.", 'cost' => 5, 'icon_url' => '/images/gifts/coffee.svg', 'category' => 'casual'],
            ['name' => 'Rose', 'description' => 'A classic symbol of romance.', 'cost' => 10, 'icon_url' => '/images/gifts/rose.svg', 'category' => 'romantic'],
            ['name' => 'Cocktail', 'description' => 'Cheers!', 'cost' => 15, 'icon_url' => '/images/gifts/cocktail.svg', 'category' => 'party'],
            ['name' => 'Teddy Bear', 'description' => 'Sending you a hug.', 'cost' => 25, 'icon_url' => '/images/gifts/teddy.svg', 'category' => 'cute'],
            ['name' => 'Rocket', 'description' => 'To the moon!', 'cost' => 50, 'icon_url' => '/images/gifts/rocket.svg', 'category' => 'fun'],
            ['name' => 'Diamond', 'description' => 'You are precious.', 'cost' => 100, 'icon_url' => '/images/gifts/diamond.svg', 'category' => 'luxury'],
        ];

        foreach ($gifts as $gift) {
            DB::table('gifts')->updateOrInsert(['name' => $gift['name']], $gift + ['is_active' => true, 'updated_at' => now(), 'created_at' => now()]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_gifts');
        Schema::dropIfExists('gifts');
    }
};
