<?php

namespace Tests\Unit;

use App\Services\MediaUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaUploadServiceTest extends TestCase
{
    public function test_it_returns_placeholder_for_video()
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('video.mp4', 1000, 'video/mp4');
        $senderId = 1;

        $result = MediaUploadService::store($file, $senderId, 'video');

        $this->assertNotNull($result['thumbnail_url']);
        $this->assertStringContainsString('/images/placeholders/video-thumb.png', $result['thumbnail_url']);
    }

    public function test_it_returns_null_thumbnail_for_image()
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('photo.jpg');
        $senderId = 1;

        $result = MediaUploadService::store($file, $senderId, 'image');

        $this->assertNull($result['thumbnail_url']);
    }
}
