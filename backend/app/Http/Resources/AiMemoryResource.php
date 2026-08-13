<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\AiMemory
 */
class AiMemoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'scope' => $this->scope,
            'label' => $this->label,
            'value' => $this->value,
            'pinned' => $this->pinned,
            'source' => $this->source,
            'event_id' => $this->event_id,
            'event_title' => $this->whenLoaded('event', fn () => $this->event?->title),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
