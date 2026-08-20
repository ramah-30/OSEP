<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\BookingRequest
 */
class BookingRequestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'planner_id' => $this->planner_id,
            'planner_name' => $this->whenLoaded('planner', fn () => $this->planner?->full_name),
            'provider_type' => $this->providerType(),
            'provider_name' => $this->providerName(),
            'vendor_id' => $this->vendor_id,
            'venue_id' => $this->venue_id,
            'event_id' => $this->event_id,
            'event_title' => $this->whenLoaded('event', fn () => $this->event?->title),
            'title' => $this->title,
            'event_date' => $this->event_date?->toDateString(),
            'guest_count' => $this->guest_count,
            'budget' => $this->budget !== null ? (float) $this->budget : null,
            'requirements' => $this->requirements,
            'attachments' => $this->attachments ?? [],
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'is_open' => $this->status->isOpen(),
            'response_note' => $this->response_note,
            'responded_at' => $this->responded_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'quotations_count' => $this->whenCounted('quotations'),
        ];
    }
}
