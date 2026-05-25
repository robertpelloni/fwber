<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Journal;
use App\Models\ProximityArtifact;
use App\Models\Topic;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TopicHubFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_follow_and_unfollow_topic(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->postJson('/api/topics/music/follow')
            ->assertOk()
            ->assertJsonPath('topic.slug', 'music')
            ->assertJsonPath('topic.is_followed', true);

        $this->assertDatabaseHas('topic_user_follows', [
            'user_id' => $user->id,
        ]);

        $this->deleteJson('/api/topics/music/follow')
            ->assertOk()
            ->assertJsonPath('topic.slug', 'music')
            ->assertJsonPath('topic.is_followed', false);

        $this->assertDatabaseMissing('topic_user_follows', [
            'user_id' => $user->id,
            'topic_id' => Topic::query()->where('slug', 'music')->value('id'),
        ]);
    }

    public function test_topic_detail_aggregates_visible_journals_groups_and_artifacts(): void
    {
        $viewer = User::factory()->create();
        UserProfile::factory()->for($viewer, 'user')->create();

        $author = User::factory()->create();
        UserProfile::factory()->for($author, 'user')->create();

        $topic = Topic::query()->where('slug', 'music')->firstOrFail();

        Group::factory()->create([
            'name' => 'Midnight Music Circle',
            'created_by_user_id' => $author->id,
            'privacy' => 'public',
            'is_active' => true,
            'category' => 'music',
            'tags' => ['music', 'dj'],
            'matching_enabled' => true,
        ]);

        Journal::query()->create([
            'user_id' => $author->id,
            'title' => 'Warehouse notes',
            'content' => 'A long-form field note from the dance floor.',
            'visibility' => Journal::VISIBILITY_PUBLIC,
            'tags' => ['music', 'nightlife'],
            'mood_emoji' => '🎵',
            'accent_color' => '#111827',
        ]);

        Journal::query()->create([
            'user_id' => $author->id,
            'title' => 'Private rehearsal',
            'content' => 'Should stay hidden from non-friends.',
            'visibility' => Journal::VISIBILITY_PRIVATE,
            'tags' => ['music'],
            'mood_emoji' => '🔒',
            'accent_color' => '#000000',
        ]);

        ProximityArtifact::factory()->create([
            'user_id' => $author->id,
            'content' => 'Sunrise set in Eastern Market.',
            'meta' => ['topic_slug' => 'music'],
        ]);

        ProximityArtifact::factory()->create([
            'user_id' => $author->id,
            'content' => 'Trail meetup.',
            'meta' => ['topic_slug' => 'outdoors'],
        ]);

        Sanctum::actingAs($viewer);

        $response = $this->getJson('/api/topics/music');

        $response->assertOk()
            ->assertJsonPath('topic.slug', 'music')
            ->assertJsonCount(1, 'groups')
            ->assertJsonCount(1, 'journals')
            ->assertJsonCount(1, 'artifacts')
            ->assertJsonPath('groups.0.category', 'music')
            ->assertJsonPath('journals.0.title', 'Warehouse notes')
            ->assertJsonPath('artifacts.0.meta.topic_slug', 'music');

        $this->assertSame(1, $response->json('topic.group_count'));
        $this->assertSame(1, $response->json('topic.journal_count'));
        $this->assertSame(1, $response->json('topic.artifact_count'));
    }

    public function test_proximity_feed_filters_by_topic_slug(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        ProximityArtifact::factory()->create([
            'user_id' => $user->id,
            'location_lat' => 42.64,
            'location_lng' => -83.00,
            'meta' => ['topic_slug' => 'music'],
        ]);

        ProximityArtifact::factory()->create([
            'user_id' => $user->id,
            'location_lat' => 42.6401,
            'location_lng' => -83.0001,
            'meta' => ['topic_slug' => 'gaming'],
        ]);

        $this->getJson('/api/proximity/feed?lat=42.64&lng=-83.00&radius=500&topic_slug=music')
            ->assertOk()
            ->assertJsonCount(1, 'artifacts')
            ->assertJsonPath('artifacts.0.meta.topic_slug', 'music');
    }
}
