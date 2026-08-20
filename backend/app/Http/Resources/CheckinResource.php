<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Checkin
 */
class CheckinResource extends JsonResource
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
            'method' => $this->method,
            'party_size' => $this->party_size,
            'notes' => $this->notes,
            'checked_in_at' => $this->checked_in_at?->toIso8601String(),
            'operator' => $this->whenLoaded('operator', fn () => $this->operator?->name),
        ];
    }
}
