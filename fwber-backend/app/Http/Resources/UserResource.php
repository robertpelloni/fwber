<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            "id" => $this->id,
            "name" => $this->name,
            "email" => $this->email,
            "emailVerifiedAt" => optional($this->email_verified_at)->toAtomString(),
            "lastSeenAt" => optional($this->last_seen_at)->toIso8601String(),
            "createdAt" => optional($this->created_at)->toAtomString(),
            "updatedAt" => optional($this->updated_at)->toAtomString(),
            "profile" => new ProfileResource($this->whenLoaded("profile")),
        ];
    }
}
