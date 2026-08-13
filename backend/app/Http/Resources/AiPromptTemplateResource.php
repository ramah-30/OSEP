<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\AiPromptTemplate
 */
class AiPromptTemplateResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'category' => $this->category,
            'description' => $this->description,
            'variables' => $this->variables ?? [],
            'current_version' => $this->current_version,
            'usage_count' => $this->usage_count,
            'pinned' => $this->pinned,
            'event_id' => $this->event_id,
            'event_title' => $this->whenLoaded('event', fn () => $this->event?->title),
            'scope' => $this->event_id ? 'event' : 'general',
            'body' => $this->whenLoaded('currentVersion', fn () => $this->currentVersion?->body),
            'versions_count' => $this->when(isset($this->versions_count), fn () => $this->versions_count),
            'versions' => AiPromptVersionResource::collection($this->whenLoaded('versions')),
            'last_used_at' => $this->last_used_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
