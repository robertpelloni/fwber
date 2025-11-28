<?php

namespace Tests\Feature;

use App\Services\MediaUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MessageMediaTest extends TestCase
{
    public function test_media_upload_service_stores_file()
    {
        Storage::fake('public');
        
        $file = UploadedFile::fake()->image('message.jpg');
        $senderId = 123;
        
        $result = MediaUploadService::store($file, $senderId, 'image');
        
        $this->assertArrayHasKey('media_url', $result);
        $this->assertArrayHasKey('media_type', $result);
        
        // Extract path from URL to check storage
        // URL is like /storage/messages/123/hash.jpg
        $path = str_replace('/storage/', '', $result['media_url']);
        
        Storage::disk('public')->assertExists($path);
    }
}
