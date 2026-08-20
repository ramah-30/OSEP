<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\SavedItem
 */
class SavedItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'collection_id' => $this->collection_id,
            'provider_type' => $this->providerType(),
            'provider_name' => $this->providerName(),
            'vendor_id' => $this->vendor_id,
            'venue_id' => $this->venue_id,
            'note' => $this->note,
            // The saved provider, serialised for the card grid.
            'vendor' => $this->when(
                $this->vendor_id && $this->relationLoaded('vendor') && $this->vendor,
                fn () => new VendorResource($this->vendor),
            ),
            'venue' => $this->when(
                $this->venue_id && $this->relationLoaded('venue') && $this->venue,
                fn () => new MarketplaceVenueResource($this->venue),
            ),
        ];
    }
}
