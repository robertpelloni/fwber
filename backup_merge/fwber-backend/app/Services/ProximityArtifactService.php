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

        return ProximityArtifact::create([
            'user_id' => $user->id,
            'type' => $data['type'],
            'content' => $sanitized,
            'location_lat' => $data['location_lat'],
            'location_lng' => $data['location_lng'],
            'visibility_radius_m' => $data['visibility_radius_m'] ?? 1000,
            'expires_at' => $expiresAt,
            'moderation_status' => 'clean',
        ]);
    }

    public function flagArtifact(ProximityArtifact $artifact, User $actor): void
    {
        // Simple flagging: count flags in meta -> escalate if threshold reached
        $meta = $artifact->meta ?? [];
        $flags = ($meta['flags'] ?? 0) + 1;
        $meta['flags'] = $flags;
        if ($flags >= 3 && $artifact->moderation_status === 'clean') {
            $artifact->moderation_status = 'flagged';
        }
        $artifact->meta = $meta;
        $artifact->save();
    }

    public function pruneExpired(): int
    {
        return ProximityArtifact::where('expires_at', '<=', now())->delete();
    }

    private function assertType(string $type): void
    {
        if (!isset(self::TYPE_EXPIRY[$type])) {
            throw new \InvalidArgumentException("Unsupported artifact type: {$type}");
        }
    }

    private function computeExpiry(string $type): \DateTimeInterface
    {
        return now()->modify(self::TYPE_EXPIRY[$type]);
    }

    private function enforceDailyCap(int $userId, string $type): void
    {
        $cap = self::DAILY_CAP[$type];
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
        // Reject disallowed patterns
        $patterns = [
            '/https?:\/\//i',
            '/www\./i',
            '/\b\d{3}[- .]?\d{3}[- .]?\d{4}\b/', // phone
            '/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', // email
        ];
        foreach ($patterns as $p) {
            if (preg_match($p, $content)) {
                throw new \RuntimeException('Content contains disallowed contact/link information');
            }
        }
        // Trim and collapse whitespace
        $clean = trim(preg_replace('/\s+/', ' ', $content));
        if (strlen($clean) < 1 || strlen($clean) > 500) {
            throw new \RuntimeException('Content length invalid');
        }
        return $clean;
    }
}
