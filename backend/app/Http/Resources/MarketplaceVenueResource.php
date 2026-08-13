<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\MarketplaceVenue
 */
class MarketplaceVenueResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => 'venue',
            'owner_id' => $this->owner_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'venue_type' => $this->venue_type,
            'description' => $this->description,
            'setting' => $this->setting->value,
            'setting_label' => $this->setting->label(),
            'capacity' => $this->capacity,
            'min_capacity' => $this->min_capacity,
            'dimensions' => $this->dimensions,
            'layout_options' => $this->layout_options ?? [],
            'setup_time' => $this->setup_time,
            'breakdown_time' => $this->breakdown_time,
            'included_equipment' => $this->included_equipment ?? [],
            'facilities' => $this->facilities ?? [],
            'accessibility' => $this->accessibility ?? [],
            'restrictions' => $this->restrictions,
            'parking_available' => $this->parking_available,
            'parking_capacity' => $this->parking_capacity,
            'price' => $this->price !== null ? (float) $this->price : null,
            'currency' => $this->currency,
            'price_unit' => $this->price_unit,
            'address' => $this->address,
            'location' => $this->location,
            'latitude' => $this->latitude !== null ? (float) $this->latitude : null,
            'longitude' => $this->longitude !== null ? (float) $this->longitude : null,
            'contact_person' => $this->contact_person,
            'contact_phone' => $this->contact_phone,
            'contact_email' => $this->contact_email,
            'booking_terms' => $this->booking_terms,
            'cover_image_url' => $this->cover_image_url,
            'verification_level' => $this->verification_level->value,
            'verification_label' => $this->verification_level->label(),
            'is_verified' => $this->verification_level->isVerified(),
            'is_featured' => $this->is_featured,
            'is_suspended' => $this->is_suspended,
            'is_published' => $this->is_published,
            'rating' => $this->rating !== null ? (float) $this->rating : null,
            'reviews_count' => $this->reviews_count,
            'images' => VenueImageResource::collection($this->whenLoaded('images')),
            'reviews' => ReviewResource::collection($this->whenLoaded('reviews')),
        ];
    }
}
