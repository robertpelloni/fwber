<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $achievements = [
            [
                'name' => 'Icon',
                'description' => 'Received 50 vouches',
                'icon' => 'star',
                'category' => 'social',
                'reward_tokens' => 500,
                'criteria_type' => 'vouches_received',
                'criteria_value' => 50,
                'is_hidden' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Viral Star',
                'description' => 'Got 100 views on a single content',
                'icon' => 'trending-up',
                'category' => 'viral',
                'reward_tokens' => 1000,
                'criteria_type' => 'viral_views',
                'criteria_value' => 100,
                'is_hidden' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ];

        foreach ($achievements as $achievement) {
            if (!DB::table('achievements')->where('name', $achievement['name'])->exists()) {
                DB::table('achievements')->insert($achievement);
            }
        }
    }

    public function down(): void
    {
        DB::table('achievements')->whereIn('name', ['Icon', 'Viral Star'])->delete();
    }
};
