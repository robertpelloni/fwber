<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\Log;

class PhotoEncryptionService
{
    /**
     * Encrypt content and store it to the specified disk and path.
     *
     * @param string $content Raw file content
     * @param string $path Storage path
     * @param string $disk Storage disk name
     * @return bool
     */
    public function encryptAndStore(string $content, string $path, string $disk = 'local'): bool
    {
        try {
            // Encrypt the content using Laravel's encryption (AES-256-CBC by default)
            $encrypted = Crypt::encrypt($content);
            return Storage::disk($disk)->put($path, $encrypted);
        } catch (\Exception $e) {
            Log::error("Encryption failed for path {$path}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Retrieve encrypted content from storage and decrypt it.
     *
     * @param string $path Storage path
     * @param string $disk Storage disk name
     * @return string Decrypted content
     * @throws FileNotFoundException
     * @throws \Illuminate\Contracts\Encryption\DecryptException
     */
    public function decryptAndRetrieve(string $path, string $disk = 'local'): string
    {
        if (!Storage::disk($disk)->exists($path)) {
            throw new FileNotFoundException("File not found at path: {$path}");
        }

        $encryptedContent = Storage::disk($disk)->get($path);
        
        try {
            return Crypt::decrypt($encryptedContent);
        } catch (\Exception $e) {
            Log::error("Decryption failed for path {$path}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Stream the decrypted content.
     * Note: Laravel's Crypt doesn't support streaming decryption natively easily without loading into memory.
     * For large files, we might need a different approach (openssl_decrypt with streams).
     * For now, we assume files fit in memory (max 5MB as per PhotoController).
     */
    public function decryptStream(string $path, string $disk = 'local')
    {
        $content = $this->decryptAndRetrieve($path, $disk);
        
        // Create a temporary stream
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $content);
        rewind($stream);
        
        return $stream;
    }
}
