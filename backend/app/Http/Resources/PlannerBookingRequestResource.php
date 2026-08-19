<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\PlannerBookingRequest */
class PlannerBookingRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'reference'       => $this->reference,
            'status'          => $this->status->value,
            'status_label'    => $this->status->label(),
            'event_type'      => $this->event_type,
            'event_date'      => $this->event_date?->toDateString(),
            'expected_guests' => $this->expected_guests,
            'proposed_budget' => $this->proposed_budget !== null ? (float) $this->proposed_budget : null,
            'venue'           => $this->venue,
            'location'        => $this->location,
            'message'         => $this->message,
            'planner_note'    => $this->planner_note,
            'quoted_budget'   => $this->quoted_budget !== null ? (float) $this->quoted_budget : null,
            'created_at'      => $this->created_at->toIso8601String(),
            'planner'         => $this->whenLoaded('planner', fn () => [
                'id'           => $this->planner->id,
                'full_name'    => $this->planner->full_name,
                'email'        => $this->planner->email,
                'avatar_url'   => $this->planner->avatar_url,
                'company_name' => $this->planner->plannerProfile?->company_name,
                'booking_slug' => $this->planner->plannerProfile?->booking_slug,
            ]),
            'client'          => $this->whenLoaded('client', fn () => [
                'id'         => $this->client->id,
                'full_name'  => $this->client->full_name,
                'email'      => $this->client->email,
                'phone'      => $this->client->phone,
                'avatar_url' => $this->client->avatar_url,
            ]),
            'event_id'        => $this->event_id,
        ];
    }
}
