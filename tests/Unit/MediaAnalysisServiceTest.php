<?php

namespace Tests\Unit;

use App\Services\MediaAnalysisService;
use Tests\TestCase;

class MediaAnalysisServiceTest extends TestCase
{
    private MediaAnalysisService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MediaAnalysisService();
    }

    public function test_analyze_image_returns_mock_data()
    {
        $result = $this->service->analyze('http://example.com/image.jpg', 'image');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('safe', $result);
        $this->assertArrayHasKey('labels', $result);
        $this->assertArrayHasKey('moderation_labels', $result);
        $this->assertTrue($result['safe']);
    }

    public function test_analyze_video_returns_mock_data()
    {
        $result = $this->service->analyze('http://example.com/video.mp4', 'video');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('safe', $result);
        $this->assertArrayHasKey('labels', $result);
        $this->assertTrue($result['safe']);
    }

    public function test_analyze_audio_returns_mock_data()
    {
        $result = $this->service->analyze('http://example.com/audio.mp3', 'audio');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('safe', $result);
        $this->assertArrayHasKey('transcription', $result);
        $this->assertArrayHasKey('sentiment', $result);
        $this->assertTrue($result['safe']);
    }
}
