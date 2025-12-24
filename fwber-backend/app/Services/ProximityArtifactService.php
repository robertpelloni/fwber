<?php

namespace App\Services;

use App\Models\ProximityArtifact;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ProximityArtifactService
{
    public function __construct(
        private TokenDistributionService $tokenService
    ) {}

    private const TYPE_EXPIRY = [
        'chat' => '+45 minutes',
        'board_post' => '+48 hours',
        'announce' => '+2 hours',
        'token_drop' => '+24 hours',
    ];

    private const DAILY_CAP = [
        'chat' => 30,
        'board_post' => 10,
        'announce' => 15,
        'token_drop' => 5,
    ];

    public function createArtifact(User $user, array $data): ProximityArtifact
    {
        $this->assertType($data['type']);
        $this->enforceDailyCap($user->id, $data['type']);
        $sanitized = $this->sanitizeContent($data['content']);
        $expiresAt = $this->computeExpiry($data['type']);

        $meta = $data['meta'] ?? [];

        if ($data['type'] === 'token_drop') {
            $amount = $data['amount'] ?? 0;
            if ($amount <= 0) {
                throw new \InvalidArgumentException("Amount must be positive for token drops");
            }

            $this->tokenService->spendTokens($user, $amount, "Created Token Drop");
            $meta['amount'] = $amount;
            $meta['claimed'] = false;
        }

        return ProximityArtifact::create([
            'user_id' => $user->id,
            'type' => $data['type'],
            'content' => $sanitized,
            'location_lat' => $data['location_lat'],
            'location_lng' => $data['location_lng'],
            'visibility_radius_m' => $data['visibility_radius_m'] ?? 1000,
            'expires_at' => $expiresAt,
            'moderation_status' => 'clean',
            'meta' => $meta,
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

    public function claimArtifact(ProximityArtifact $artifact, User $claimer, float $lat, float $lng): float
    {
        if ($artifact->type !== 'token_drop') {
            throw new \InvalidArgumentException("Not a token drop");
        }

        $meta = $artifact->meta;
        if (($meta['claimed'] ?? false)) {
            throw new \RuntimeException("Already claimed");
        }

        // Check distance (50 meters)
        $dist = $this->calculateDistance($lat, $lng, $artifact->location_lat, $artifact->location_lng);
        if ($dist > 50) {
             throw new \RuntimeException("Too far away to claim (Distance: " . round($dist) . "m)");
        }

        $amount = $meta['amount'] ?? 0;

        DB::transaction(function() use ($artifact, $claimer, $amount, $meta) {
             $this->tokenService->awardTokens($claimer, $amount, 'token_drop_claim', "Claimed Drop {$artifact->id}");

             $meta['claimed'] = true;
             $meta['claimed_by'] = $claimer->id;
             $meta['claimed_at'] = now()->toIso8601String();
             $artifact->meta = $meta;
             $artifact->save();
        });

        return $amount;
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2): float
    {
        $earthRadius = 6371000; // meters

        $lat1 = deg2rad($lat1);
        $lon1 = deg2rad($lon1);
        $lat2 = deg2rad($lat2);
        $lon2 = deg2rad($lon2);

        $latDelta = $lat2 - $lat1;
        $lonDelta = $lon2 - $lon1;

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos($lat1) * cos($lat2) *
             sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
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
