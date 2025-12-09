<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\User;
use App\Models\Chatroom;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class GroupChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_group_creates_chatroom()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->postJson('/api/groups', [
            'name' => 'Test Group',
            'description' => 'Test Description',
            'privacy' => 'public',
        ]);

        $response->assertStatus(201);
        
        $group = Group::first();
        $this->assertNotNull($group->chatroom_id);
        
        $chatroom = Chatroom::find($group->chatroom_id);
        $this->assertNotNull($chatroom);
        $this->assertEquals('Test Group', $chatroom->name);
        $this->assertEquals('group', $chatroom->type);
        $this->assertTrue($chatroom->hasMember($user));
    }

    public function test_joining_group_adds_to_chatroom()
    {
        $creator = User::factory()->create();
        $user = User::factory()->create();
        
        $this->actingAs($creator);
        $groupResponse = $this->postJson('/api/groups', [
            'name' => 'Test Group',
            'privacy' => 'public',
        ]);
        $groupId = $groupResponse->json('id');
        $group = Group::find($groupId);

        $this->actingAs($user);
        $response = $this->postJson("/api/groups/{$groupId}/join");
        
        $response->assertStatus(200);
        
        $chatroom = Chatroom::find($group->chatroom_id);
        $this->assertTrue($chatroom->hasMember($user));
    }

    public function test_leaving_group_removes_from_chatroom()
    {
        $creator = User::factory()->create();
        $user = User::factory()->create();
        
        $this->actingAs($creator);
        $groupResponse = $this->postJson('/api/groups', [
            'name' => 'Test Group',
            'privacy' => 'public',
        ]);
        $groupId = $groupResponse->json('id');
        $group = Group::find($groupId);

        $this->actingAs($user);
        $this->postJson("/api/groups/{$groupId}/join");
        
        $chatroom = Chatroom::find($group->chatroom_id);
        $this->assertTrue($chatroom->hasMember($user));

        $response = $this->postJson("/api/groups/{$groupId}/leave");
        $response->assertStatus(200);
        
        $this->assertFalse($chatroom->hasMember($user));
    }
}
