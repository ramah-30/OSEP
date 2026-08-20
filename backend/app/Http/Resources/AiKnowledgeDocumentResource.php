<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\AiKnowledgeDocument
 */
class AiKnowledgeDocumentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'category' => $this->category,
            'content' => $this->content,
            'scope' => $this->event_id ? 'event' : 'global',
            'event_id' => $this->event_id,
            'event_title' => $this->whenLoaded('event', fn () => $this->event?->title),
            'pinned' => $this->pinned,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
