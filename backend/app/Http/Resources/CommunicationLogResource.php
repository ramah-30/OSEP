<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\CommunicationLog
 */
class CommunicationLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'guest_id' => $this->guest_id,
            'guest_name' => $this->whenLoaded('guest', fn () => $this->guest?->full_name),
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'channel' => $this->channel,
            'title' => $this->title,
            'detail' => $this->detail,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
