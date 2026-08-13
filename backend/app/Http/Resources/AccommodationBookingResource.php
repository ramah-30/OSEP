<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\AccommodationBooking
 */
class AccommodationBookingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'guest_name' => $this->guest_name,
            'check_in' => $this->check_in?->toDateString(),
            'check_out' => $this->check_out?->toDateString(),
            'nights' => $this->nights,
            'rooms' => $this->rooms,
            'guests' => $this->guests,
            'price_per_night' => (float) $this->price_per_night,
            'total_price' => (float) $this->total_price,
            'currency' => $this->currency,
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'special_requests' => $this->special_requests,
            'created_at' => $this->created_at?->toIso8601String(),
            'accommodation' => $this->whenLoaded('accommodation', fn () => $this->accommodation ? [
                'id' => $this->accommodation->id,
                'name' => $this->accommodation->name,
                'slug' => $this->accommodation->slug,
                'city' => $this->accommodation->city,
                'cover_image_url' => $this->accommodation->cover_image_url,
                'star_rating' => $this->accommodation->star_rating,
            ] : null),
            'room_type' => $this->whenLoaded('roomType', fn () => $this->roomType ? [
                'id' => $this->roomType->id,
                'name' => $this->roomType->name,
            ] : null),
            'client' => $this->whenLoaded('client', fn () => $this->client ? [
                'id' => $this->client->id,
                'full_name' => $this->client->full_name,
            ] : null),
            'event' => $this->whenLoaded('event', fn () => $this->event ? [
                'id' => $this->event->id,
                'title' => $this->event->title,
            ] : null),
        ];
    }
}
