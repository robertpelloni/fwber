<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\Message;
use App\Models\Photo;
use App\Models\MatchAction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use ZipArchive;
use Illuminate\Support\Facades\File;

class ExportUserData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user;

    /**
     * Create a new job instance.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $userId = $this->user->id;
        $exportId = now()->timestamp;
        $basePath = "exports/{$userId}/{$exportId}";
        $fullPath = storage_path("app/{$basePath}");

        Log::info("Starting data export for user {$userId}");

        try {
            // Create directory
            if (!File::exists($fullPath)) {
                File::makeDirectory($fullPath, 0755, true);
            }

            // 1. Export Profile Data
            $profileData = $this->user->load('profile')->toArray();
            File::put("{$fullPath}/profile.json", json_encode($profileData, JSON_PRETTY_PRINT));

            // 2. Export Messages
            $sentMessages = Message::where('user_id', $userId)->get()->toArray();
            $receivedMessages = Message::where('target_user_id', $userId)->get()->toArray();
            File::put("{$fullPath}/messages_sent.json", json_encode($sentMessages, JSON_PRETTY_PRINT));
            File::put("{$fullPath}/messages_received.json", json_encode($receivedMessages, JSON_PRETTY_PRINT));

            // 3. Export Matches
            $matches = MatchAction::where('user_id', $userId)->get()->toArray();
            File::put("{$fullPath}/matches.json", json_encode($matches, JSON_PRETTY_PRINT));

            // 4. Export Photos (Files + Metadata)
            $photosDir = "{$fullPath}/photos";
            File::makeDirectory($photosDir);
            
            $photos = Photo::where('user_id', $userId)->get();
            $photoMetadata = [];

            foreach ($photos as $photo) {
                $photoMetadata[] = $photo->toArray();
                
                // Copy photo file if it exists
                if (Storage::disk('public')->exists($photo->path)) {
                    $fileName = basename($photo->path);
                    File::copy(
                        Storage::disk('public')->path($photo->path),
                        "{$photosDir}/{$fileName}"
                    );
                }
            }
            File::put("{$fullPath}/photos_metadata.json", json_encode($photoMetadata, JSON_PRETTY_PRINT));

            // 5. Create ZIP Archive
            $zipFileName = "fwber-data-export-{$exportId}.zip";
            $zipPath = storage_path("app/exports/{$userId}/{$zipFileName}");
            
            $zip = new ZipArchive;
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
                $files = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($fullPath),
                    \RecursiveIteratorIterator::LEAVES_ONLY
                );

                foreach ($files as $name => $file) {
                    if (!$file->isDir()) {
                        $filePath = $file->getRealPath();
                        $relativePath = substr($filePath, strlen($fullPath) + 1);
                        $zip->addFile($filePath, $relativePath);
                    }
                }
                $zip->close();
            }

            // Cleanup raw files
            File::deleteDirectory($fullPath);

            Log::info("Data export completed for user {$userId}. File: {$zipFileName}");

            // Notify user (TODO: Implement Email/Notification)
            // For now, we update a cache key or status record if we had one.

        } catch (\Exception $e) {
            Log::error("Data export failed for user {$userId}: " . $e->getMessage());
            // Cleanup on failure
            if (File::exists($fullPath)) {
                File::deleteDirectory($fullPath);
            }
            throw $e;
        }
    }
}
