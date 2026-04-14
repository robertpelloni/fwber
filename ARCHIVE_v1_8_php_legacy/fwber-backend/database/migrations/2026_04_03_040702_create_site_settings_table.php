<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('value');
            $table->string('type')->default('string'); // string, int, float, bool
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Seed initial settings
        \DB::table('site_settings')->insert([
            ['key' => 'daily_token_bonus', 'value' => '5.0', 'type' => 'float', 'description' => 'Tokens awarded for daily login'],
            ['key' => 'proposal_min_tokens', 'value' => '100', 'type' => 'int', 'description' => 'Minimum tokens to create a proposal'],
            ['key' => 'vote_participation_min', 'value' => '10', 'type' => 'int', 'description' => 'Minimum tokens to vote'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
