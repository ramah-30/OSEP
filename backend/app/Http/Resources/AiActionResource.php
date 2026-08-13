<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\AiAction
 */
class AiActionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'source' => $this->source,
            'type' => $this->type,
            'title' => $this->title,
            'summary' => $this->summary,
            'params' => $this->params ?? [],
            'status' => $this->status,
            'result' => $this->result,
            'error' => $this->error,
            'event_id' => $this->event_id,
            'event_title' => $this->whenLoaded('event', fn () => $this->event?->title),
            'conversation_id' => $this->ai_conversation_id,
            'created_at' => $this->created_at?->toIso8601String(),
            'executed_at' => $this->executed_at?->toIso8601String(),
        ];
    }
}
