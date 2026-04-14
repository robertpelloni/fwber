<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TopicResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'label' => $this->label,
            'description' => $this->description,
            'emoji' => $this->emoji,
            'category' => $this->category,
            'aliases' => $this->aliases ?? [],
            'is_featured' => (bool) $this->is_featured,
            'sort_order' => $this->sort_order,
            'follower_count' => (int) ($this->follower_count ?? 0),
            'group_count' => (int) ($this->group_count ?? 0),
            'journal_count' => (int) ($this->journal_count ?? 0),
            'artifact_count' => (int) ($this->artifact_count ?? 0),
            'is_followed' => (bool) ($this->is_followed ?? false),
            'match_source' => $this->when(isset($this->match_source), $this->match_source),
        ];
    }
}
