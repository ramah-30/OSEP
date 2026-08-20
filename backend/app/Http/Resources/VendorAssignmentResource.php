<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\VendorAssignment
 */
class VendorAssignmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event_id' => $this->event_id,
            'vendor_id' => $this->vendor_id,
            'vendor_name' => $this->vendor_name,
            'service' => $this->service,
            'package' => $this->package,
            'price' => $this->price !== null ? (float) $this->price : null,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'notes' => $this->notes,
            'vendor' => $this->whenLoaded('vendor', fn () => $this->vendor ? [
                'id' => $this->vendor->id,
                'full_name' => $this->vendor->full_name,
                'business_name' => $this->vendor->vendorProfile?->business_name,
                'avatar_url' => $this->vendor->avatar_url,
            ] : null),
        ];
    }
}
