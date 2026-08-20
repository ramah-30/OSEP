<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\AiPromptVersion
 */
class AiPromptVersionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'version' => $this->version,
            'body' => $this->body,
            'note' => $this->note,
            'author' => $this->whenLoaded('author', fn () => $this->author?->full_name),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
