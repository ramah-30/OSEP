<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\RsvpResponse
 */
class RsvpResponseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'guest_id' => $this->guest_id,
            'guest_name' => $this->whenLoaded('guest', fn () => $this->guest->full_name),
            'response' => $this->response->value,
            'response_label' => $this->response->label(),
            'additional_guests' => $this->additional_guests,
            'meal_choice' => $this->meal_choice,
            'special_requirements' => $this->special_requirements,
            'message' => $this->message,
            'responded_at' => $this->responded_at?->toIso8601String(),
        ];
    }
}
