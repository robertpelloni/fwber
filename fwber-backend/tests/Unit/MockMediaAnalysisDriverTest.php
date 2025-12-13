<?php

namespace Tests\Unit;

use App\Services\MediaAnalysis\Drivers\MockMediaAnalysisDriver;
use App\Services\MediaAnalysis\MediaAnalysisResult;
use Tests\TestCase;

class MockMediaAnalysisDriverTest extends TestCase
{
    private MockMediaAnalysisDriver $driver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->driver = new MockMediaAnalysisDriver();
    }

    public function test_analyze_image_returns_mock_data()
    {
        $result = $this->driver->analyze('http://example.com/image.jpg', 'image');

        $this->assertInstanceOf(MediaAnalysisResult::class, $result);
        $this->assertTrue($result->safe);
        $this->assertIsArray($result->labels);
        $this->assertIsArray($result->moderationLabels);
    }

    public function test_analyze_video_returns_mock_data()
    {
        $result = $this->driver->analyze('http://example.com/video.mp4', 'video');

        $this->assertInstanceOf(MediaAnalysisResult::class, $result);
        $this->assertTrue($result->safe);
        $this->assertIsArray($result->labels);
    }

    public function test_analyze_audio_returns_mock_data()
    {
        $result = $this->driver->analyze('http://example.com/audio.mp3', 'audio');

        $this->assertInstanceOf(MediaAnalysisResult::class, $result);
        $this->assertTrue($result->safe);
        // Audio result metadata check
        $this->assertArrayHasKey('transcription', $result->metadata);
        $this->assertArrayHasKey('sentiment', $result->metadata);
    }
    
    public function test_unsafe_image_detection()
    {
        $result = $this->driver->analyze('http://example.com/unsafe_image.jpg', 'image');
        
        $this->assertFalse($result->safe);
        $this->assertContains('Explicit', $result->labels);
    }
}
