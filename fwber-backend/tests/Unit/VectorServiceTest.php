<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\VectorService;
use App\Models\UserProfile;
use Illuminate\Support\Facades\Redis;
use OpenAI\Laravel\Facades\OpenAI;
use OpenAI\Responses\Embeddings\CreateResponse;
use OpenAI\Responses\Meta\MetaInformation;

class VectorServiceTest extends TestCase
{
    public function test_store_profile_generates_and_stores_embedding()
    {
        // Mock OpenAI using Fake
        OpenAI::fake([
            'embeddings/create' => CreateResponse::from([
                'object' => 'list',
                'data' => [
                    [
                        'object' => 'embedding',
                        'embedding' => array_fill(0, 1536, 0.1),
                        'index' => 0,
                    ]
                ],
                'model' => 'text-embedding-3-small',
                'usage' => [
                    'prompt_tokens' => 8,
                    'total_tokens' => 8,
                ]
            ], MetaInformation::from([])),
        ]);

        // Mock Redis
        Redis::shouldReceive('hmset')
            ->once()
            ->withArgs(function ($key, $data) {
                return str_starts_with($key, 'user:profile:') &&
                       isset($data['embedding']) &&
                       isset($data['gender']);
            });

        $service = new VectorService();
        $profile = new UserProfile([
            'user_id' => 1,
            'bio' => 'Test Bio',
            'gender' => 'male',
            'birthdate' => now()->subYears(25)->format('Y-m-d')
        ]);

        $service->storeProfile($profile);
    }

    public function test_search_executes_redis_command()
    {
        // Mock Redis Command
        Redis::shouldReceive('command')
            ->with('FT.SEARCH', \Mockery::any())
            ->once()
            ->andReturn([
                1, // count
                'user:profile:2', // key
                ['score', '0.9', 'user_id', '2'] // fields
            ]);

        $service = new VectorService();
        $vector = array_fill(0, 1536, 0.1);
        
        $results = $service->search($vector);
        
        $this->assertCount(1, $results);
        $this->assertEquals('2', $results[0]['user_id']);
    }
}
