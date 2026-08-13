<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\AiMessage
 */
class AiMessageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'role' => $this->role,
            'content' => $this->content,
            'agent' => $this->agent,
            'model' => $this->model,
            'grounded' => (bool) ($this->meta['grounded'] ?? false),
            'my_rating' => $this->whenLoaded('feedback', fn () => $this->feedback->first()?->rating?->value),
            'action' => $this->whenLoaded('action', fn () => $this->action ? new AiActionResource($this->action) : null),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
