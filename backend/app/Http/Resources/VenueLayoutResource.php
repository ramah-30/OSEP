<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\VenueLayout
 */
class VenueLayoutResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event_id' => $this->event_id,
            'layout_name' => $this->layout_name,
            'venue_name' => $this->venue_name,
            'venue_type' => $this->venue_type,
            'setting' => $this->setting,
            'width' => (float) $this->width,
            'height' => (float) $this->height,
            'unit' => $this->unit,
            'max_capacity' => $this->max_capacity,
            'entry_points' => $this->entry_points,
            'exit_points' => $this->exit_points,
            'version' => $this->version,
            'meta' => $this->meta ?? [],
            'objects_count' => $this->whenCounted('objects'),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'objects' => VenueObjectResource::collection($this->whenLoaded('objects')),
        ];
    }
}
