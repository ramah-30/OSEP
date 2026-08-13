<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\VenueObject
 */
class VenueObjectResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uid' => $this->uid,
            'object_type' => $this->object_type,
            'object_name' => $this->object_name,
            'x' => (float) $this->x_position,
            'y' => (float) $this->y_position,
            'width' => (float) $this->width,
            'height' => (float) $this->height,
            'rotation' => (float) $this->rotation,
            'color' => $this->color,
            'layer' => $this->layer,
            'properties' => $this->properties ?? [],
            'seating' => SeatingAssignmentResource::collection($this->whenLoaded('seating')),
        ];
    }
}
