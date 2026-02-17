<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\MediaAnalysis\Drivers\GoogleVisionDriver;
use Google\Cloud\Vision\V1\ImageAnnotatorClient;
use Google\Cloud\Vision\V1\AnnotateImageResponse;
use Google\Cloud\Vision\V1\SafeSearchAnnotation;
use Google\Cloud\Vision\V1\EntityAnnotation;
use Google\Cloud\Vision\V1\Likelihood;
use Mockery;
use Illuminate\Support\Facades\Storage;

class GoogleVisionDriverTest extends TestCase
{
    public function test_analyze_detects_safe_content()
    {
        // Mock Response
        $safeSearch = Mockery::mock(SafeSearchAnnotation::class);
        $safeSearch->shouldReceive('getAdult')->andReturn(Likelihood::VERY_UNLIKELY);
        $safeSearch->shouldReceive('getViolence')->andReturn(Likelihood::VERY_UNLIKELY);
        $safeSearch->shouldReceive('getRacy')->andReturn(Likelihood::VERY_UNLIKELY);

        $response = Mockery::mock(AnnotateImageResponse::class);
        $response->shouldReceive('getSafeSearchAnnotation')->andReturn($safeSearch);
        $response->shouldReceive('getLabelAnnotations')->andReturn([]);

        // Mock Client
        $client = Mockery::mock(ImageAnnotatorClient::class);
        $client->shouldReceive('annotateImage')
            ->once()
            ->andReturn($response);
        $client->shouldReceive('close')->once();

        // Create Driver with Mock
        $driver = new GoogleVisionDriver($client);

        // Create dummy file
        $path = storage_path('app/public/test.jpg');
        // Ensure directory exists
        if (!is_dir(dirname($path))) mkdir(dirname($path), 0777, true);
        file_put_contents($path, 'dummy content');

        $result = $driver->analyze($path, 'image');

        $this->assertTrue($result->safe);
        $this->assertEquals(1.0, $result->confidence);
        
        // Cleanup
        @unlink($path);
    }

    public function test_analyze_detects_unsafe_content()
    {
        // Mock Response
        $safeSearch = Mockery::mock(SafeSearchAnnotation::class);
        $safeSearch->shouldReceive('getAdult')->andReturn(Likelihood::LIKELY); // Unsafe
        $safeSearch->shouldReceive('getViolence')->andReturn(Likelihood::VERY_UNLIKELY);
        $safeSearch->shouldReceive('getRacy')->andReturn(Likelihood::VERY_UNLIKELY);

        $response = Mockery::mock(AnnotateImageResponse::class);
        $response->shouldReceive('getSafeSearchAnnotation')->andReturn($safeSearch);
        $response->shouldReceive('getLabelAnnotations')->andReturn([]);

        // Mock Client
        $client = Mockery::mock(ImageAnnotatorClient::class);
        $client->shouldReceive('annotateImage')
            ->once()
            ->andReturn($response);
        $client->shouldReceive('close')->once();

        // Create Driver with Mock
        $driver = new GoogleVisionDriver($client);

        // Create dummy file
        $path = storage_path('app/public/test_unsafe.jpg');
        // Ensure directory exists
        if (!is_dir(dirname($path))) mkdir(dirname($path), 0777, true);
        file_put_contents($path, 'dummy content');

        $result = $driver->analyze($path, 'image');

        $this->assertFalse($result->safe);
        $this->assertEquals(0.0, $result->confidence);
        $this->assertContains('Content flagged as unsafe (Adult/Violence).', $result->moderationLabels);

        // Cleanup
        @unlink($path);
    }
}
