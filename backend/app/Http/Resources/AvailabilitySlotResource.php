<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A single calendar day for a vendor or a venue. Shared shape so the frontend
 * availability calendar is identical for both provider kinds.
 *
 * @mixin \App\Models\VendorAvailability|\App\Models\VenueAvailability
 */
class AvailabilitySlotResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'date' => $this->date->toDateString(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'note' => $this->note,
        ];
    }
}
