<?php

namespace App\Services;

use App\Models\ProximityArtifact;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ProximityArtifactService
{
    private const TYPE_EXPIRY = [
        'chat' => '+45 minutes',
        'board_post' => '+48 hours',
        'announce' => '+2 hours',
    ];

    private const DAILY_CAP = [
        'chat' => 30,
        'board_post' => 10,
        'announce' => 15,
    ];

    public function createArtifact(User $user, array $data): ProximityArtifact
    {
        $this->assertType($data['type']);
        $this->enforceDailyCap($user->id, $data['type']);
        $sanitized = $this->sanitizeContent($data['content']);
        $expiresAt = $this->computeExpiry($data['type']);

        $meta = $data['meta'] ?? [];

        return ProximityArtifact::create([
            'user_id' => $user->id,
            'type' => $data['type'],
            'content' => $sanitized,
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'geohash' => \App\Helpers\GeohashHelper::encode($data['latitude'], $data['longitude'], 8),
            'visibility_radius_m' => $data['visibility_radius_m'] ?? 1000,
            'expires_at' => $expiresAt,
            'metadata' => $meta,
        ]);
    }

    public function flagArtifact(ProximityArtifact $artifact, User $actor): void
    {
        $artifact->update([
            'is_flagged' => true,
            'flag_reason' => 'Reported by user ' . $actor->id
        ]);
    }

    public function pruneExpired(): int
    {
        return ProximityArtifact::where('expires_at', '<=', now())->delete();
    }

    private function assertType(string $type): void
    {
        if (! isset(self::TYPE_EXPIRY[$type])) {
            throw new \InvalidArgumentException("Unsupported artifact type: {$type}");
        }
    }

    private function computeExpiry(string $type): \DateTimeInterface
    {
        return now()->modify(self::TYPE_EXPIRY[$type]);
    }

    private function enforceDailyCap(int $userId, string $type): void
    {
        $cap = self::DAILY_CAP[$type] ?? 10;
        $count = ProximityArtifact::where('user_id', $userId)
            ->where('type', $type)
            ->where('created_at', '>=', now()->startOfDay())
            ->count();
        if ($count >= $cap) {
            throw new \RuntimeException('Daily posting cap reached for type: '.$type);
        }
    }

    public function sanitizeContent(string $content): string
    {
        $patterns = [
            '/https?:\/\//i',
            '/www\./i',
            '/\b\d{3}[- .]?\d{3}[- .]?\d{4}\b/',
            '/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i',
        ];
        foreach ($patterns as $p) {
            if (preg_match($p, $content)) {
                throw new \RuntimeException('Content contains disallowed contact/link information');
            }
        }
        $clean = trim(preg_replace('/\s+/', ' ', $content));
        if (strlen($clean) < 1 || strlen($clean) > 500) {
            throw new \RuntimeException('Content length invalid');
        }

        return $clean;
    }
}
