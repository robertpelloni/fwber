<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserMatch;
use App\Models\Message;
use App\Models\Photo;
use App\Models\RelationshipTier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TierSystemTestSeeder extends Seeder
{
    /**
     * Seed test data for the relationship tier system.
     */
    public function run(): void
    {
        $this->command->info('Creating test users...');
        
        // Create test users
        $user1 = User::firstOrCreate(
            ['email' => 'alice@test.com'],
            [
                'name' => 'Alice',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );

        $user2 = User::firstOrCreate(
            ['email' => 'bob@test.com'],
            [
                'name' => 'Bob',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );

        $this->command->info("✓ Created users: {$user1->name} (ID: {$user1->id}) and {$user2->name} (ID: {$user2->id})");

        // Create a match between them
        $this->command->info('Creating match...');
        
        $match = UserMatch::firstOrCreate(
            [
                'user1_id' => $user1->id,
                'user2_id' => $user2->id,
            ],
            [
                'is_active' => true,
                'last_message_at' => null,
            ]
        );

        $this->command->info("✓ Created match (ID: {$match->id})");

        // Create relationship tier for this match
        $this->command->info('Creating relationship tier...');
        
        $matchedAt = now()->subDays(5); // Matched 5 days ago
        
        $tier = RelationshipTier::firstOrCreate(
            ['match_id' => $match->id],
            [
                'current_tier' => 'discovery',
                'messages_exchanged' => 0,
                'days_connected' => 0,
                'has_met_in_person' => false,
                'first_matched_at' => $matchedAt,
            ]
        );

        $this->command->info("✓ Created relationship tier (Current: {$tier->current_tier})");

        // Create some test photos for both users
        $this->command->info('Creating test photos...');
        
        // AI photos (always visible)
        foreach (range(1, 2) as $i) {
            $filename = "ai-photo-{$user1->id}-{$i}.jpg";
            Photo::firstOrCreate(
                [
                    'user_id' => $user1->id,
                    'filename' => $filename,
                ],
                [
                    'photo_type' => 'ai',
                    'original_filename' => $filename,
                    'file_path' => "photos/test/{$filename}",
                    'mime_type' => 'image/jpeg',
                    'file_size' => 100000,
                    'is_primary' => $i === 1,
                    'sort_order' => $i,
                ]
            );

            $filename = "ai-photo-{$user2->id}-{$i}.jpg";
            Photo::firstOrCreate(
                [
                    'user_id' => $user2->id,
                    'filename' => $filename,
                ],
                [
                    'photo_type' => 'ai',
                    'original_filename' => $filename,
                    'file_path' => "photos/test/{$filename}",
                    'mime_type' => 'image/jpeg',
                    'file_size' => 100000,
                    'is_primary' => $i === 1,
                    'sort_order' => $i,
                ]
            );
        }

        // Real photos (unlocked by tier)
        foreach (range(3, 7) as $i) {
            $filename = "real-photo-{$user1->id}-{$i}.jpg";
            Photo::firstOrCreate(
                [
                    'user_id' => $user1->id,
                    'filename' => $filename,
                ],
                [
                    'photo_type' => 'real',
                    'original_filename' => $filename,
                    'file_path' => "photos/test/{$filename}",
                    'mime_type' => 'image/jpeg',
                    'file_size' => 150000,
                    'is_primary' => false,
                    'sort_order' => $i,
                ]
            );

            $filename = "real-photo-{$user2->id}-{$i}.jpg";
            Photo::firstOrCreate(
                [
                    'user_id' => $user2->id,
                    'filename' => $filename,
                ],
                [
                    'photo_type' => 'real',
                    'original_filename' => $filename,
                    'file_path' => "photos/test/{$filename}",
                    'mime_type' => 'image/jpeg',
                    'file_size' => 150000,
                    'is_primary' => false,
                    'sort_order' => $i,
                ]
            );
        }

        $this->command->info('✓ Created 2 AI photos and 5 real photos for each user');

        // Create some test messages to advance the tier
        $this->command->info('Creating test messages...');
        
        $messages = [
            ['from' => $user1->id, 'to' => $user2->id, 'text' => 'Hey! Nice to match with you!', 'days_ago' => 4],
            ['from' => $user2->id, 'to' => $user1->id, 'text' => 'Thanks! You seem interesting too.', 'days_ago' => 3],
            ['from' => $user1->id, 'to' => $user2->id, 'text' => 'What do you like to do for fun?', 'days_ago' => 2],
        ];

        foreach ($messages as $msg) {
            $sentAt = now()->subDays($msg['days_ago']);
            Message::create([
                'sender_id' => $msg['from'],
                'receiver_id' => $msg['to'],
                'content' => $msg['text'],
                'message_type' => 'text',
                'sent_at' => $sentAt,
                'created_at' => $sentAt,
                'updated_at' => $sentAt,
            ]);
        }

        $messageCount = count($messages);
        $this->command->info("✓ Created {$messageCount} test messages");

        // Update tier with message count
        $tier->messages_exchanged = count($messages);
        $tier->last_message_at = now();
        $tier->save();
        
        // Recalculate tier based on metrics
        $tier->updateDaysConnected();
        $newTier = $tier->calculateTier();
        
        if ($newTier !== $tier->current_tier) {
            $tier->current_tier = $newTier;
            $tier->save();
            $this->command->info("✓ Tier upgraded to: {$newTier}");
        }

        $this->command->newLine();
        $this->command->info('═══════════════════════════════════════');
        $this->command->info('🎉 Test Data Created Successfully!');
        $this->command->info('═══════════════════════════════════════');
        $this->command->newLine();
        
        $this->command->table(
            ['Field', 'Value'],
            [
                ['User 1', "{$user1->name} (ID: {$user1->id}) - alice@test.com"],
                ['User 2', "{$user2->name} (ID: {$user2->id}) - bob@test.com"],
                ['Password', 'password123 (for both users)'],
                ['Match ID', $match->id],
                ['Current Tier', $tier->current_tier],
                ['Messages Exchanged', $tier->messages_exchanged],
                ['Days Connected', $tier->days_connected],
                ['Has Met In Person', $tier->has_met_in_person ? 'Yes' : 'No'],
                ['AI Photos per User', '2'],
                ['Real Photos per User', '5'],
            ]
        );

        $this->command->newLine();
        $this->command->info('Next Steps:');
        $this->command->info('1. Login as alice@test.com or bob@test.com (password: password123)');
        $this->command->info('2. Visit http://localhost:3000/tier-demo to test the tier system');
        $this->command->info('3. Send messages to increment the tier');
        $this->command->info('4. Watch photos unlock as tier advances!');
        $this->command->newLine();
    }
}
