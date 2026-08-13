<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Guest
 */
class GuestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event_id' => $this->event_id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'phone' => $this->phone,
            'email' => $this->email,
            'gender' => $this->gender,
            'category' => $this->category,
            'rsvp_status' => $this->rsvp_status->value,
            'rsvp_status_label' => $this->rsvp_status->label(),
            'invitation_status' => $this->invitation_status->value,
            'invitation_status_label' => $this->invitation_status->label(),
            'checkin_status' => $this->checkin_status->value,
            'checkin_status_label' => $this->checkin_status->label(),
            'meal_preference' => $this->meal_preference,
            'dietary_restrictions' => $this->dietary_restrictions,
            'accessibility_requirements' => $this->accessibility_requirements,
            'plus_ones_allowed' => $this->plus_ones_allowed,
            'seat_number' => $this->seat_number,
            'notes' => $this->notes,
            'rsvp_token' => $this->rsvp_token,
            'rsvp_responded_at' => $this->rsvp_responded_at?->toIso8601String(),
            'checked_in_at' => $this->checked_in_at?->toIso8601String(),
            'archived_at' => $this->archived_at?->toIso8601String(),
            'is_archived' => $this->archived_at !== null,
            'has_qr' => $this->when($this->relationLoaded('qrCode'), fn () => $this->qrCode !== null),
            'qr_token' => $this->whenLoaded('qrCode', fn () => $this->qrCode?->token),
            'invitations' => InvitationResource::collection($this->whenLoaded('invitations')),
            'rsvp_responses' => RsvpResponseResource::collection($this->whenLoaded('rsvpResponses')),
            'communication_logs' => CommunicationLogResource::collection($this->whenLoaded('communicationLogs')),
            'checkin' => new CheckinResource($this->whenLoaded('checkin')),
        ];
    }
}
