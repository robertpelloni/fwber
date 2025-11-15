<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardDataSeeder extends Seeder
{
    /**
     * Seed sample data for dashboard testing
     */
    public function run(): void
    {
        // Get or create test users
        $user1Email = 'test1@fwber.com';
        $user2Email = 'test2@fwber.com';
        
        $user1 = DB::table('users')->where('email', $user1Email)->first();
        if (!$user1) {
            $user1Id = DB::table('users')->insertGetId([
                'name' => 'Test User 1',
                'email' => $user1Email,
                'password' => bcrypt('password123'),
                'email_verified_at' => Carbon::now(),
                'created_at' => Carbon::now()->subDays(30),
                'updated_at' => Carbon::now(),
            ]);
            $user1 = DB::table('users')->find($user1Id);
            $this->command->info("✓ Created test user: {$user1Email}");
        }
        
        $user2 = DB::table('users')->where('email', $user2Email)->first();
        if (!$user2) {
            $user2Id = DB::table('users')->insertGetId([
                'name' => 'Test User 2',
                'email' => $user2Email,
                'password' => bcrypt('password123'),
                'email_verified_at' => Carbon::now(),
                'created_at' => Carbon::now()->subDays(25),
                'updated_at' => Carbon::now(),
            ]);
            $user2 = DB::table('users')->find($user2Id);
            $this->command->info("✓ Created test user: {$user2Email}");
        }
        
        $this->command->info("Seeding dashboard data for user: {$user1->email}");
        
        // Seed matches
        if (!DB::table('matches')->where('user1_id', $user1->id)->exists()) {
            DB::table('matches')->insert([
                [
                    'user1_id' => $user1->id,
                    'user2_id' => $user2->id,
                    'status' => 'accepted',
                    'match_score' => 85,
                    'is_active' => true,
                    'created_at' => Carbon::now()->subDays(2),
                    'updated_at' => Carbon::now()->subDays(2),
                ],
            ]);
            $this->command->info('✓ Created sample matches');
        }
        
        // Seed messages
        if (!DB::table('messages')->where('sender_id', $user1->id)->exists()) {
            $now1 = Carbon::now()->subDays(1);
            $now2 = Carbon::now()->subHours(12);
            $now3 = Carbon::now()->subHours(3);
            
            DB::table('messages')->insert([
                [
                    'sender_id' => $user2->id,
                    'receiver_id' => $user1->id,
                    'content' => 'Hey! Nice to match with you!',
                    'message_type' => 'text',
                    'is_read' => true,
                    'sent_at' => $now1,
                    'read_at' => $now1->copy()->addMinutes(30),
                    'created_at' => $now1,
                    'updated_at' => $now1,
                ],
                [
                    'sender_id' => $user1->id,
                    'receiver_id' => $user2->id,
                    'content' => 'Thanks! Looking forward to chatting',
                    'message_type' => 'text',
                    'is_read' => false,
                    'sent_at' => $now2,
                    'read_at' => null,
                    'created_at' => $now2,
                    'updated_at' => $now2,
                ],
                [
                    'sender_id' => $user2->id,
                    'receiver_id' => $user1->id,
                    'content' => 'Want to grab coffee sometime?',
                    'message_type' => 'text',
                    'is_read' => false,
                    'sent_at' => $now3,
                    'read_at' => null,
                    'created_at' => $now3,
                    'updated_at' => $now3,
                ],
            ]);
            $this->command->info('✓ Created sample messages');
        }
        
        // Seed profile views
        if (!DB::table('profile_views')->where('viewed_user_id', $user1->id)->exists()) {
            DB::table('profile_views')->insert([
                [
                    'viewed_user_id' => $user1->id,
                    'viewer_user_id' => $user2->id,
                    'viewer_ip' => '192.168.1.100',
                    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                    'created_at' => Carbon::now()->subDays(3),
                ],
                [
                    'viewed_user_id' => $user1->id,
                    'viewer_user_id' => $user2->id,
                    'viewer_ip' => '192.168.1.101',
                    'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0)',
                    'created_at' => Carbon::now()->subDays(1),
                ],
                [
                    'viewed_user_id' => $user1->id,
                    'viewer_user_id' => null,
                    'viewer_ip' => '192.168.1.102',
                    'user_agent' => 'Mozilla/5.0 (Android 11)',
                    'created_at' => Carbon::now()->subHours(8),
                ],
                [
                    'viewed_user_id' => $user1->id,
                    'viewer_user_id' => $user2->id,
                    'viewer_ip' => '192.168.1.103',
                    'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X)',
                    'created_at' => Carbon::now()->subHours(2),
                ],
            ]);
            $this->command->info('✓ Created sample profile views');
        }
        
        $this->command->info('✅ Dashboard data seeded successfully!');
        $this->command->line('');
        $this->command->info("Test user credentials:");
        $this->command->line("Email: {$user1->email}");
        $this->command->line("Login and check /dashboard to see stats");
    }
}
