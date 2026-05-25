<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Journal;
use App\Models\Topic;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SceneDiscoveryFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_matches_include_scene_overlap_from_followed_topics_and_scene_tags(): void
    {
        $viewer = User::factory()->create();
        UserProfile::factory()->create([
            'user_id' => $viewer->id,
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'interests' => ['coffee', 'nightlife', 'design'],
        ]);

        $candidate = User::factory()->create();
        UserProfile::factory()->create([
            'user_id' => $candidate->id,
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'interests' => ['coffee', 'warehouse', 'art'],
        ]);

        $topic = Topic::firstOrCreate(
            ['slug' => 'nightlife'],
            [
                'label' => 'Nightlife',
                'description' => 'Late-night scenes and venues.',
                'emoji' => '🌃',
                'category' => 'culture',
                'aliases' => ['warehouse'],
                'is_featured' => true,
                'sort_order' => 1,
            ]
        );

        $viewer->followedTopics()->attach($topic->id, ['followed_at' => now()]);
        $candidate->followedTopics()->attach($topic->id, ['followed_at' => now()]);

        Journal::create([
            'user_id' => $candidate->id,
            'title' => 'Warehouse pulse',
            'content' => 'Still chasing the best late-night rooms.',
            'visibility' => 'public',
            'tags' => ['warehouse', 'coffee'],
        ]);

        $response = $this->actingAs($viewer)->getJson('/api/matches');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'matches')
            ->assertJsonPath('matches.0.id', $candidate->id)
            ->assertJsonPath('matches.0.scene_overlap.shared_topics.0.slug', 'nightlife')
            ->assertJsonPath('matches.0.scene_overlap.shared_topic_count', 1);

        $sceneTags = $response->json('matches.0.scene_overlap.shared_scene_tags');
        $this->assertGreaterThanOrEqual(3, count($sceneTags));
        $this->assertContains('coffee', $sceneTags);
        $this->assertContains('nightlife', $sceneTags);
        $this->assertContains('warehouse', $sceneTags);
    }

    public function test_public_profile_includes_scene_summary(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();

        UserProfile::factory()->create([
            'user_id' => $owner->id,
            'interests' => ['coffee', 'design'],
        ]);
        UserProfile::factory()->create(['user_id' => $viewer->id]);

        $topic = Topic::firstOrCreate(
            ['slug' => 'coffee-culture'],
            [
                'label' => 'Coffee Culture',
                'description' => 'Cafes, tastings, and coffee runs.',
                'emoji' => '☕',
                'category' => 'lifestyle',
                'aliases' => ['espresso'],
                'is_featured' => true,
                'sort_order' => 2,
            ]
        );

        $owner->followedTopics()->attach($topic->id, ['followed_at' => now()]);

        $group = Group::factory()->create([
            'created_by_user_id' => $owner->id,
            'privacy' => 'public',
            'category' => 'Nightlife',
            'tags' => ['warehouse', 'downtempo'],
        ]);

        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $owner->id,
            'role' => 'member',
            'joined_at' => now(),
            'is_active' => true,
            'is_banned' => false,
        ]);

        Journal::create([
            'user_id' => $owner->id,
            'title' => 'Cafe loop',
            'content' => 'Met great people after the tasting.',
            'visibility' => 'public',
            'tags' => ['coffee', 'espresso'],
        ]);

        $response = $this->actingAs($viewer)->getJson("/api/users/{$owner->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.profile.scene_summary.followed_topics.0.slug', 'coffee-culture')
            ->assertJsonPath('data.profile.scene_summary.stats.followed_topic_count', 1)
            ->assertJsonPath('data.profile.scene_summary.stats.public_group_count', 1)
            ->assertJsonPath('data.profile.scene_summary.stats.visible_journal_count', 1);

        $sceneTags = $response->json('data.profile.scene_summary.scene_tags');
        $interestTopicSlugs = collect($response->json('data.profile.interest_topics'))->pluck('slug');

        $this->assertTrue($interestTopicSlugs->contains('coffee-culture'));
        $this->assertContains('coffee', $sceneTags);
        $this->assertContains('warehouse', $sceneTags);
    }
}
