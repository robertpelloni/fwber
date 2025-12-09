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

        $thumbnailUrl = null;
        // Placeholder for future video thumbnail generation
        if ($resolvedType === 'video') {
            // In future: generate thumbnail and store under messages/{senderId}/thumbnails
            // $thumbnailPath = ...
            // $thumbnailUrl = Storage::url($thumbnailPath);
        }

        return [
            'media_url' => $mediaUrl,
            'media_type' => $mediaType,
            'thumbnail_url' => $thumbnailUrl,
        ];
    }
}
