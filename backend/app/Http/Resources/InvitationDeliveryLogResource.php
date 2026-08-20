<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\InvitationDeliveryLog
 */
class InvitationDeliveryLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'channel' => $this->channel,
            'detail' => $this->detail,
            'occurred_at' => $this->occurred_at?->toIso8601String(),
        ];
    }
}
