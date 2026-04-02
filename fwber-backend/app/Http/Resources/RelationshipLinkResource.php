<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RelationshipLinkResource extends JsonResource
{
    private ?int $ownerId = null;

    public function forOwner(int $ownerId): self
    {
        $this->ownerId = $ownerId;

        return $this;
    }

    public function toArray(Request $request): array
    {
        $ownerId = $this->ownerId ?? $request->user()?->id ?? $this->user_id;
        $counterpart = $this->counterpartFor($ownerId);

        return [
            'id' => $this->id,
            'relationship_type' => $this->relationship_type,
            'relationship_type_label' => ucfirst($this->relationship_type),
            'visibility' => $this->visibility,
            'visibility_label' => ucfirst($this->visibility),
            'note' => $this->note,
            'requested_at' => $this->requested_at?->toISOString(),
            'confirmed_at' => $this->confirmed_at?->toISOString(),
            'is_confirmed' => $this->isConfirmed(),
            'requested_by_user_id' => $this->user_id,
            'related_user' => $counterpart ? [
                'id' => $counterpart->id,
                'name' => $counterpart->name,
                'display_name' => $counterpart->profile?->display_name,
                'avatar_url' => $counterpart->avatar_url,
            ] : null,
        ];
    }
}
