<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MatchResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $profile = $this->profile;

        return [
            "id" => $this->id,
            "name" => $profile?->display_name ?? $this->name,
            "email" => $this->email,
            "avatarUrl" => $profile?->avatar_url,
            "bio" => $profile?->bio,
            "locationDescription" => $profile?->location_description,
            "compatibilityScore" => (int) ($this->compatibility_score ?? 0),
            "lastSeenAt" => optional($this->last_seen_at)->toIso8601String(),
        ];
    }
}
