<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin \App\Models\EventDocument
 */
class DocumentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event_id' => $this->event_id,
            'task_id' => $this->task_id,
            'name' => $this->name,
            'category' => $this->category,
            'url' => Storage::disk('public')->url($this->file_path),
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'version' => $this->version,
            'created_at' => $this->created_at?->toIso8601String(),
            'uploader' => $this->whenLoaded('uploader', fn () => $this->uploader ? [
                'id' => $this->uploader->id,
                'full_name' => $this->uploader->full_name,
            ] : null),
        ];
    }
}
