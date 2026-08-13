<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Invitation
 */
class InvitationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event_id' => $this->event_id,
            'guest_id' => $this->guest_id,
            'guest_name' => $this->whenLoaded('guest', fn () => $this->guest->full_name),
            'template_id' => $this->template_id,
            'channel' => $this->channel->value,
            'channel_label' => $this->channel->label(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'subject' => $this->subject,
            'kind' => $this->meta['kind'] ?? 'invitation',
            'scheduled_for' => $this->scheduled_for?->toIso8601String(),
            'sent_at' => $this->sent_at?->toIso8601String(),
            'delivered_at' => $this->delivered_at?->toIso8601String(),
            'opened_at' => $this->opened_at?->toIso8601String(),
            'failed_reason' => $this->failed_reason,
            'created_at' => $this->created_at?->toIso8601String(),
            'delivery_logs' => InvitationDeliveryLogResource::collection($this->whenLoaded('deliveryLogs')),
        ];
    }
}
