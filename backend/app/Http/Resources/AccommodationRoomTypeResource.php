<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\AccommodationRoomType
 */
class AccommodationRoomTypeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price_per_night' => (float) $this->price_per_night,
            'currency' => $this->currency,
            'capacity' => $this->capacity,
            'bed_configuration' => $this->bed_configuration,
            'size_sqm' => $this->size_sqm,
            'amenities' => $this->amenities ?? [],
            'image_url' => $this->image_url,
            'total_rooms' => $this->total_rooms,
            'rooms_available' => $this->when(isset($this->rooms_available), fn () => $this->rooms_available),
        ];
    }
}
