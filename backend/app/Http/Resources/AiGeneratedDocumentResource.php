<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * @mixin \App\Models\AiGeneratedDocument
 */
class AiGeneratedDocumentResource extends JsonResource
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
            'template_key' => $this->template_key,
            'format' => $this->format,
            'status' => $this->status?->value,
            'event_id' => $this->event_id,
            'event_title' => $this->whenLoaded('event', fn () => $this->event?->title),
            'grounded' => (bool) ($this->meta['grounded'] ?? false),
            'driver' => $this->meta['driver'] ?? null,
            'model' => $this->model,
            'inputs' => $this->inputs ?? [],
            'my_rating' => $this->whenLoaded('feedback', fn () => $this->feedback->first()?->rating?->value),
            'preview' => Str::limit(trim(preg_replace('/\s+/', ' ', preg_replace('/[#*_>`|\-]+/', ' ', (string) $this->content))), 140),
            'content' => $this->content,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
