<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PrivacySecurityService
{
    public function moderateContent(string $content, string $type = 'text'): array
    {
        // Simple regex-based moderation for core
        $explicitKeywords = ['spam', 'scam', 'fake', 'bot'];
        $textLower = strtolower($content);
        $flags = [];
        foreach ($explicitKeywords as $keyword) {
            if (strpos($textLower, $keyword) !== false) $flags[] = $keyword;
        }

        return [
            'is_safe' => empty($flags),
            'confidence' => 1.0,
            'flags' => $flags,
            'action' => empty($flags) ? 'approve' : 'reject',
        ];
    }

    public function anonymizeUserData(int $userId): void
    {
        $user = User::find($userId);
        if (! $user) return;

        $user->update([
            'name' => 'Deleted User',
            'email' => "deleted_{$userId}@fwber.me",
        ]);

        if ($user->profile) {
            $user->profile->update([
                'display_name' => 'Deleted User',
                'bio' => 'This profile has been deleted',
            ]);
        }

        $user->photos()->delete();
        $user->sentMessages()->delete();
        $user->receivedMessages()->delete();
        $user->matchesAsUser1()->delete();
        $user->matchesAsUser2()->delete();

        Log::info("User {$userId} data anonymized");
    }
}
