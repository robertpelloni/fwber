<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserProfile;
use App\Models\Photo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class PrivacySecurityService
{
    private $contentModerationApi;
    private $imageModerationApi;

    public function __construct()
    {
        // In a real implementation, these would be actual API endpoints
        $this->contentModerationApi = config('services.content_moderation.api_url');
        $this->imageModerationApi = config('services.image_moderation.api_url');
    }

    public function moderateContent(string $content, string $type = 'text'): array
    {
        $cacheKey = 'moderation_' . md5($content . $type);
        
        return Cache::remember($cacheKey, 3600, function () use ($content, $type) {
            // Use configured moderation driver
            $driver = config('services.content_moderation.driver', 'mock');

            if ($driver === 'aws') {
                return $this->moderateWithAws($content, $type);
            } elseif ($driver === 'google') {
                return $this->moderateWithGoogle($content, $type);
            }

            // Fallback to mock/regex
            return $this->moderateWithMock($content, $type);
        });
    }

    private function moderateWithMock(string $content, string $type): array
    {
        // Text content moderation
        if ($type === 'text') {
            return $this->moderateTextRegex($content);
        }
        
        // Image content moderation
        if ($type === 'image') {
            return $this->moderateImageBasic($content);
        }

        return ['is_safe' => true, 'confidence' => 1.0, 'flags' => [], 'action' => 'approve'];
    }

    private function moderateWithAws(string $contentUrl, string $type): array
    {
        // Use the AwsRekognitionDriver directly via the container or new instance
        // Assuming content is a file path/URL
        
        try {
            /** @var \App\Services\MediaAnalysis\Drivers\AwsRekognitionDriver $driver */
            $driver = app(\App\Services\MediaAnalysis\Drivers\AwsRekognitionDriver::class);
            
            // Rekognition driver expects a file path relative to public disk
            $result = $driver->analyze($contentUrl, $type);

            return [
                'is_safe' => $result->isSafe,
                'confidence' => $result->confidence,
                'flags' => $result->unsafeLabels,
                'action' => $result->isSafe ? 'approve' : 'reject'
            ];
        } catch (\Exception $e) {
            Log::error("PrivacySecurityService: AWS Moderation failed: " . $e->getMessage());
            // Fallback to strict mock if service fails to avoid letting unsafe content through
            return ['is_safe' => false, 'confidence' => 0.0, 'flags' => ['moderation_service_error'], 'action' => 'reject'];
        }
    }

    private function moderateWithGoogle(string $contentUrl, string $type): array
    {
        try {
            /** @var \App\Services\MediaAnalysis\Drivers\GoogleVisionDriver $driver */
            $driver = app(\App\Services\MediaAnalysis\Drivers\GoogleVisionDriver::class);
            
            // Google driver expects a file path (local or url depending on setup)
            // For now, assume it handles what AWS driver handled (path relative to storage or absolute)
            // But GoogleVisionDriver uses file_get_contents($url) so it needs a reachable path.
            // If $contentUrl is a relative storage path, we might need to resolve it.
            // AWS driver used Storage::disk('public')->get(), Google driver currently does file_get_contents.
            
            // Let's ensure consistency. If it's a relative path in storage, resolve full path.
            if (Storage::disk('public')->exists($contentUrl)) {
                $fullPath = Storage::disk('public')->path($contentUrl);
            } else {
                $fullPath = $contentUrl;
            }

            $result = $driver->analyze($fullPath, $type);

            return [
                'is_safe' => $result->safe,
                'confidence' => $result->confidence,
                'flags' => $result->moderationLabels, // Using moderationLabels which maps to warnings/unsafe reasons
                'action' => $result->safe ? 'approve' : 'reject'
            ];
        } catch (\Exception $e) {
            Log::error("PrivacySecurityService: Google Moderation failed: " . $e->getMessage());
            return ['is_safe' => false, 'confidence' => 0.0, 'flags' => ['moderation_service_error'], 'action' => 'reject'];
        }
    }

    private function moderateTextRegex(string $text): array
    {
        $flags = [];
        $confidence = 1.0;

        // Check for explicit content keywords
        $explicitKeywords = [
            'unsolicited', 'explicit', 'inappropriate', 'harassment',
            'spam', 'scam', 'fake', 'bot', 'seller', 'escort'
        ];

        $textLower = strtolower($text);
        foreach ($explicitKeywords as $keyword) {
            if (strpos($textLower, $keyword) !== false) {
                $flags[] = $keyword;
                $confidence -= 0.2;
            }
        }

        // Check for excessive capitalization (spam indicator)
        if (preg_match('/[A-Z]{5,}/', $text)) {
            $flags[] = 'excessive_caps';
            $confidence -= 0.1;
        }

        // Check for repeated characters (spam indicator)
        if (preg_match('/(.)\1{4,}/', $text)) {
            $flags[] = 'repeated_chars';
            $confidence -= 0.1;
        }

        // Check for suspicious patterns
        if (preg_match('/\b\d{10,}\b/', $text)) { // Phone numbers
            $flags[] = 'phone_number';
            $confidence -= 0.15;
        }

        if (preg_match('/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/', $text)) {
            $flags[] = 'email_address';
            $confidence -= 0.1;
        }

        $isSafe = $confidence > 0.5;
        $action = $isSafe ? 'approve' : ($confidence > 0.3 ? 'review' : 'reject');

        return [
            'is_safe' => $isSafe,
            'confidence' => max(0, $confidence),
            'flags' => $flags,
            'action' => $action,
        ];
    }

    private function moderateImageBasic(string $imagePath): array
    {
        // Basic file validation
        $flags = [];
        $confidence = 1.0;

        // Check file size (max 10MB)
        if (Storage::exists($imagePath)) {
            $fileSize = Storage::size($imagePath);
            if ($fileSize > 10 * 1024 * 1024) {
                $flags[] = 'file_too_large';
                $confidence -= 0.3;
            }

            // Check file type
            $mimeType = Storage::mimeType($imagePath);
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($mimeType, $allowedTypes)) {
                $flags[] = 'invalid_file_type';
                $confidence -= 0.5;
            }

            // Basic image analysis
            try {
                $imageInfo = getimagesize(Storage::path($imagePath));
                if ($imageInfo) {
                    $width = $imageInfo[0];
                    $height = $imageInfo[1];
                    
                    // Check for extremely small images (potential spam)
                    if ($width < 100 || $height < 100) {
                        $flags[] = 'image_too_small';
                        $confidence -= 0.2;
                    }
                    
                    // Check for extremely large images (potential abuse)
                    if ($width > 4000 || $height > 4000) {
                        $flags[] = 'image_too_large';
                        $confidence -= 0.1;
                    }
                }
            } catch (\Exception $e) {
                // Ignore errors reading image size for remote/S3 files if direct access fails
            }
        }

        $isSafe = $confidence > 0.5;
        $action = $isSafe ? 'approve' : ($confidence > 0.3 ? 'review' : 'reject');

        return [
            'is_safe' => $isSafe,
            'confidence' => max(0, $confidence),
            'flags' => $flags,
            'action' => $action,
        ];
    }

    public function createTemporaryPhotoAccess(int $grantingUserId, int $grantedUserId, int $photoId, int $durationHours = 24): array
    {
        $accessToken = Str::random(32);
        $expiresAt = now()->addHours($durationHours);

        // Store access permission
        Cache::put(
            "photo_access:{$accessToken}",
            [
                'granting_user_id' => $grantingUserId,
                'granted_user_id' => $grantedUserId,
                'photo_id' => $photoId,
                'expires_at' => $expiresAt->toISOString(),
            ],
            $expiresAt
        );

        return [
            'access_token' => $accessToken,
            'expires_at' => $expiresAt->toISOString(),
            'duration_hours' => $durationHours,
        ];
    }

    public function validatePhotoAccess(string $accessToken, int $requestingUserId): ?array
    {
        $accessData = Cache::get("photo_access:{$accessToken}");
        
        if (!$accessData) {
            return null; // Token expired or invalid
        }

        if ($accessData['granted_user_id'] !== $requestingUserId) {
            return null; // Wrong user
        }

        if (now()->isAfter($accessData['expires_at'])) {
            Cache::forget("photo_access:{$accessToken}");
            return null; // Expired
        }

        return $accessData;
    }

    public function revokePhotoAccess(string $accessToken): bool
    {
        return Cache::forget("photo_access:{$accessToken}");
    }

    public function verifyUserProfile(int $userId, string $verificationImagePath): array
    {
        // In a real implementation, this would use computer vision to verify the username
        $result = [
            'is_verified' => false,
            'confidence' => 0.0,
            'message' => 'Verification pending',
        ];

        // Basic validation
        $imageModeration = $this->moderateImage($verificationImagePath);
        
        if (!$imageModeration['is_safe']) {
            $result['message'] = 'Image failed moderation';
            return $result;
        }

        // Store verification request
        Cache::put(
            "verification_request:{$userId}",
            [
                'image_path' => $verificationImagePath,
                'submitted_at' => now()->toISOString(),
                'status' => 'pending',
            ],
            now()->addDays(7) // Keep for 7 days
        );

        // In a real implementation, this would trigger a manual review process
        // For now, we'll simulate automatic verification
        $result['is_verified'] = true;
        $result['confidence'] = 0.85;
        $result['message'] = 'Profile verified';

        // Update user verification status
        User::where('id', $userId)->update(['is_verified' => true]);

        return $result;
    }

    public function detectSuspiciousActivity(int $userId): array
    {
        $flags = [];
        $riskScore = 0.0;

        // Check for rapid profile views
        $recentViews = Cache::get("profile_views:{$userId}", 0);
        if ($recentViews > 100) { // More than 100 views in last hour
            $flags[] = 'excessive_profile_views';
            $riskScore += 0.3;
        }

        // Check for rapid message sending
        $recentMessages = Cache::get("messages_sent:{$userId}", 0);
        if ($recentMessages > 50) { // More than 50 messages in last hour
            $flags[] = 'excessive_messaging';
            $riskScore += 0.4;
        }

        // Check for rapid likes/swipes
        $recentLikes = Cache::get("likes_sent:{$userId}", 0);
        if ($recentLikes > 200) { // More than 200 likes in last hour
            $flags[] = 'excessive_liking';
            $riskScore += 0.2;
        }

        // Check for repeated content
        $recentContent = Cache::get("recent_content:{$userId}", []);
        if (count($recentContent) > 10) {
            $uniqueContent = array_unique($recentContent);
            if (count($uniqueContent) < count($recentContent) * 0.5) {
                $flags[] = 'repeated_content';
                $riskScore += 0.3;
            }
        }

        $isSuspicious = $riskScore > 0.5;
        $action = $isSuspicious ? 'flag_for_review' : 'monitor';

        return [
            'is_suspicious' => $isSuspicious,
            'risk_score' => $riskScore,
            'flags' => $flags,
            'action' => $action,
        ];
    }

    public function enforceRateLimit(int $userId, string $action): bool
    {
        $limits = [
            'profile_view' => 100, // per hour
            'message_send' => 50,  // per hour
            'like_send' => 200,    // per hour
            'photo_upload' => 10,   // per hour
        ];

        $limit = $limits[$action] ?? 50;
        $key = "rate_limit:{$userId}:{$action}";
        
        $current = Cache::get($key, 0);
        if ($current >= $limit) {
            return false; // Rate limit exceeded
        }

        Cache::increment($key);
        Cache::expire($key, 3600); // 1 hour

        return true;
    }

    public function encryptSensitiveData(string $data): string
    {
        $key = config('app.encryption_key');
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }

    public function decryptSensitiveData(string $encryptedData): string
    {
        $key = config('app.encryption_key');
        $data = base64_decode($encryptedData);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
    }

    public function anonymizeUserData(int $userId): void
    {
        $user = User::find($userId);
        if (!$user) {
            return;
        }

        // Anonymize user data
        $user->update([
            'name' => 'Deleted User',
            'email' => "deleted_{$userId}@fwber.me",
        ]);

        // Anonymize profile
        if ($user->profile) {
            $user->profile->update([
                'display_name' => 'Deleted User',
                'bio' => 'This profile has been deleted',
                'location_description' => 'Location hidden',
            ]);
        }

        // Delete photos
        $user->photos()->delete();

        // Delete messages
        $user->sentMessages()->delete();
        $user->receivedMessages()->delete();

        // Delete matches
        $user->matches()->delete();

        Log::info("User {$userId} data anonymized");
    }

    public function generatePrivacyReport(int $userId): array
    {
        $user = User::with('profile')->find($userId);
        if (!$user) {
            return [];
        }

        $report = [
            'user_id' => $userId,
            'generated_at' => now()->toISOString(),
            'data_points' => [],
            'privacy_score' => 100,
            'recommendations' => [],
        ];

        // Analyze profile completeness
        $profile = $user->profile;
        if ($profile) {
            $dataPoints = [
                'display_name' => !empty($profile->display_name),
                'bio' => !empty($profile->bio),
                'location' => !empty($profile->location_description),
                'photos' => $profile->photos()->count() > 0,
                'preferences' => !empty($profile->preferences),
            ];

            $report['data_points'] = $dataPoints;
            $report['privacy_score'] = (array_sum($dataPoints) / count($dataPoints)) * 100;
        }

        // Generate recommendations
        if ($report['privacy_score'] < 50) {
            $report['recommendations'][] = 'Complete your profile to improve matching';
        }
        if ($report['privacy_score'] > 80) {
            $report['recommendations'][] = 'Consider adjusting privacy settings for more control';
        }

        return $report;
    }
}
