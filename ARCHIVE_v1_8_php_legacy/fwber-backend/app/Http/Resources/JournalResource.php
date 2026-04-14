<?php

namespace App\Http\Resources;

use App\Models\Journal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JournalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'title' => $this->title,
            'content' => $this->content,
            'visibility' => $this->visibility,
            'visibility_label' => ucfirst($this->visibility),
            'circle_group_id' => $this->circle_group_id,
            'circle_group' => $this->whenLoaded('circleGroup', function () {
                if (! $this->circleGroup) {
                    return null;
                }

                return [
                    'id' => $this->circleGroup->id,
                    'name' => $this->circleGroup->name,
                    'privacy' => $this->circleGroup->privacy,
                ];
            }),
            'tags' => $this->tags ?? [],
            'mood_emoji' => $this->mood_emoji,
            'accent_color' => $this->accent_color,
            'can_edit' => $request->user()?->id === $this->user_id,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
