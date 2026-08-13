<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Venue
 */
class VenueResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event_id' => $this->event_id,
            'name' => $this->name,
            'address' => $this->address,
            'capacity' => $this->capacity,
            'setting' => $this->setting,
            'contact_person' => $this->contact_person,
            'contact_phone' => $this->contact_phone,
            'parking_available' => $this->parking_available,
            'setup_time' => $this->setup_time,
            'breakdown_time' => $this->breakdown_time,
            'notes' => $this->notes,
        ];
    }
}
