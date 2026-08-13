<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\AiRecommendation
 */
class AiRecommendationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category' => $this->category,
            'title' => $this->title,
            'description' => $this->description,
            'priority' => $this->priority->value,
            'priority_label' => $this->priority->label(),
            'confidence' => $this->confidence,
            'action_label' => $this->action_label,
            'action_type' => $this->action_type,
            'action_payload' => $this->action_payload,
            'status' => $this->status->value,
            'event_id' => $this->event_id,
            'event_title' => $this->whenLoaded('event', fn () => $this->event?->title),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
