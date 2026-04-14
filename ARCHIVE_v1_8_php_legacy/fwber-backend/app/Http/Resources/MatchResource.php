<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class MatchResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $profile = $this->profile;
        $isConfessional = (bool) $profile?->is_confessional_mode;

        return [
            'id' => $this->id,
            'name' => $isConfessional ? 'Voice Only Profile' : ($profile?->display_name ?? $this->name),
            'email' => $isConfessional ? null : $this->email,
            'avatarUrl' => $isConfessional ? null : $profile?->avatar_url,
            'bio' => $isConfessional ? null : $profile?->bio,
            'locationDescription' => $profile?->location_description,
            'distance' => (float) ($this->distance ?? 0),
            'compatibilityScore' => (int) ($this->compatibility_score ?? 0),
            'lastSeenAt' => optional($this->last_seen_at)->toIso8601String(),
            'is_confessional' => $isConfessional,
            'voice_intro_url' => $profile?->voice_intro_url,
            'gender' => $profile?->gender,
            'age' => $profile?->birthdate ? Carbon::parse($profile->birthdate)->age : null,
            'shared_interests' => $this->shared_interests ?? [],
            'shared_interest_count' => (int) ($this->shared_interest_count ?? 0),
            'scene_overlap' => $this->scene_overlap ?? null,
        ];
    }
}
