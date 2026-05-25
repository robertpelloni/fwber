<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Models\UserProfile;
use App\Services\AIMatchingService;
use App\Services\VectorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AIMatchingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_find_advanced_matches_falls_back_when_vector_pipeline_throws()
    {
        $user = User::factory()->create();
        UserProfile::factory()->create([
            'user_id' => $user->id,
            'latitude' => 42.3314,
            'longitude' => -83.0458,
        ]);

        $vectorService = Mockery::mock(VectorService::class);
        $vectorService->shouldReceive('formatProfileForEmbedding')
            ->once()
            ->andReturn('profile embedding payload');
        $vectorService->shouldReceive('generateEmbedding')
            ->once()
            ->andThrow(new \RuntimeException('Embedding provider unavailable'));

        $service = Mockery::mock(AIMatchingService::class, [$vectorService])->makePartial();
        $service->shouldReceive('fallbackToHeuristicMatching')
            ->once()
            ->with($user, [], 20)
            ->andReturn(['fallback-match']);

        $result = $service->findAdvancedMatches($user);

        $this->assertSame(['fallback-match'], $result);
    }
}
