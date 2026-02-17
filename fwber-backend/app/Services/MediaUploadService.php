<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class MediaUploadService
{
    /**
     * Store an uploaded media file for a sender and return metadata.
     *
     * @param UploadedFile $file
     * @param int $senderId
     * @param string $resolvedType One of: image, audio, video, file
     * @return array{media_url:?string, media_type:?string, thumbnail_url:?string}
     */
    public static function store(UploadedFile $file, int $senderId, string $resolvedType): array
    {
        $path = $file->store("messages/{$senderId}", 'public');
        $mediaUrl = Storage::url($path);
        $mediaType = $file->getMimeType();

        // Video Thumbnail Generation
        if ($resolvedType === 'video') {
            try {
                // Check if FFMpeg is strictly required or optional
                if (class_exists('FFMpeg\FFMpeg')) {
                    $ffmpeg = \FFMpeg\FFMpeg::create();
                    $video = $ffmpeg->open(Storage::path($path));
                    
                    $thumbnailPath = "messages/{$senderId}/thumbnails/" . pathinfo($path, PATHINFO_FILENAME) . '.jpg';
                    
                    // Extract frame at 1 second mark
                    $frame = $video->frame(\FFMpeg\Coordinate\TimeCode::fromSeconds(1));
                    $frame->save(Storage::path($thumbnailPath));
                    
                    $thumbnailUrl = Storage::url($thumbnailPath);
                } else {
                    // Fallback or log warning that FFMpeg is missing
                    \Illuminate\Support\Facades\Log::warning("FFMpeg class not found. Skipping thumbnail generation for video: {$path}");
                }
            } catch (\Throwable $e) {
                // Fail silently for thumbnails, don't block upload
                \Illuminate\Support\Facades\Log::error("Video thumbnail generation failed: " . $e->getMessage());
            }
        }

        return [
            'media_url' => $mediaUrl,
            'media_type' => $mediaType,
            'thumbnail_url' => $thumbnailUrl,
        ];
    }
}
