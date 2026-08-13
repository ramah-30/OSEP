<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\SeatingAssignment
 */
class SeatingAssignmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'seat_number' => $this->seat_number,
            'guest_id' => $this->guest_id,
            'notes' => $this->notes,
            'guest' => $this->whenLoaded('guest', fn () => $this->guest ? [
                'id' => $this->guest->id,
                'full_name' => $this->guest->full_name,
            ] : null),
        ];
    }
}
