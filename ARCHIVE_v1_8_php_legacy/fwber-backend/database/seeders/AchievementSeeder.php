<?php

namespace Database\Seeders;

use App\Models\Achievement;
use Illuminate\Database\Seeder;

class AchievementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $achievements = [
            [
                'name' => 'Profile Complete',
                'description' => 'Filled out all profile sections',
                'icon' => 'user-check',
                'category' => 'onboarding',
                'reward_tokens' => 10,
                'criteria_type' => 'profile_completeness',
                'criteria_value' => 100,
            ],
            [
                'name' => 'First Match',
                'description' => 'Received your first match',
                'icon' => 'heart',
                'category' => 'social',
                'reward_tokens' => 5,
                'criteria_type' => 'matches_count',
                'criteria_value' => 1,
            ],
            [
                'name' => 'Popular',
                'description' => 'Got 50+ profile views',
                'icon' => 'eye',
                'category' => 'social',
                'reward_tokens' => 20,
                'criteria_type' => 'profile_views',
                'criteria_value' => 50,
            ],
            [
                'name' => 'Conversationalist',
                'description' => 'Had 5 conversations',
                'icon' => 'message-circle',
                'category' => 'social',
                'reward_tokens' => 15,
                'criteria_type' => 'conversations_count',
                'criteria_value' => 5,
            ],
            [
                'name' => 'Streak Master',
                'description' => 'Maintained a 7-day streak',
                'icon' => 'flame',
                'category' => 'activity',
                'reward_tokens' => 50,
                'criteria_type' => 'streak_days',
                'criteria_value' => 7,
            ],
            [
                'name' => 'Super Connector',
                'description' => 'Referred 5 friends',
                'icon' => 'users',
                'category' => 'growth',
                'reward_tokens' => 100,
                'criteria_type' => 'referrals_count',
                'criteria_value' => 5,
            ],
            [
                'name' => 'Vibe Master',
                'description' => 'Shared your Vibe Check result',
                'icon' => 'zap',
                'category' => 'viral',
                'reward_tokens' => 10,
                'criteria_type' => 'vibe_check_shared',
                'criteria_value' => 1,
            ],
            [
                'name' => 'Connected',
                'description' => 'Reached the Connected relationship tier',
                'icon' => 'message-circle',
                'category' => 'social',
                'reward_tokens' => 10,
                'criteria_type' => 'relationship_tier',
                'criteria_value' => 3,
            ],
            [
                'name' => 'Established',
                'description' => 'Reached the Established relationship tier',
                'icon' => 'heart',
                'category' => 'social',
                'reward_tokens' => 25,
                'criteria_type' => 'relationship_tier',
                'criteria_value' => 4,
            ],
            [
                'name' => 'Verified Connection',
                'description' => 'Verified an in-person meeting',
                'icon' => 'user-check',
                'category' => 'social',
                'reward_tokens' => 100,
                'criteria_type' => 'relationship_tier',
                'criteria_value' => 5,
            ],
        ];

        foreach ($achievements as $achievement) {
            Achievement::firstOrCreate(
                ['name' => $achievement['name']],
                $achievement
            );
        }
    }
}
