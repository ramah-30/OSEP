<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Accommodation
 */
class AccommodationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'star_rating' => $this->star_rating,
            'city' => $this->city,
            'location' => $this->location,
            'address' => $this->address,
            'amenities' => $this->amenities ?? [],
            'cover_image_url' => $this->cover_image_url,
            'gallery' => $this->gallery ?? [],
            'currency' => $this->currency,
            'price_from' => (float) $this->price_from,
            'check_in_time' => $this->check_in_time,
            'check_out_time' => $this->check_out_time,
            'policies' => $this->policies,
            'contact_phone' => $this->contact_phone,
            'contact_email' => $this->contact_email,
            'is_featured' => $this->is_featured,
            'rating' => $this->reviews_avg_rating !== null ? round((float) $this->reviews_avg_rating, 1) : 0,
            'reviews_count' => (int) ($this->reviews_count ?? 0),
            'room_types' => AccommodationRoomTypeResource::collection($this->whenLoaded('roomTypes')),
        ];
    }
}
