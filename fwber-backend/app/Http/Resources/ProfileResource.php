<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            "displayName" => $this->display_name,
            "dateOfBirth" => $this->date_of_birth,
            "gender" => $this->gender,
            "pronouns" => $this->pronouns,
            "sexualOrientation" => $this->sexual_orientation,
            "relationshipStyle" => $this->relationship_style,
            "bio" => $this->bio,
            "locationLatitude" => $this->location_latitude,
            "locationLongitude" => $this->location_longitude,
            "locationDescription" => $this->location_description,
            "stiStatus" => $this->sti_status,
            "preferences" => $this->preferences,
            "avatarUrl" => $this->avatar_url,
            "createdAt" => optional($this->created_at)->toAtomString(),
            "updatedAt" => optional($this->updated_at)->toAtomString(),
        ];
    }
}
