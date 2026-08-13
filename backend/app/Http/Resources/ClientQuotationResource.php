<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\ClientQuotation
 */
class ClientQuotationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'title' => $this->title,
            'planner_id' => $this->planner_id,
            'client_id' => $this->client_id,
            'event_id' => $this->event_id,
            'currency' => $this->currency,
            'valid_until' => $this->valid_until?->toDateString(),
            'subtotal' => (float) $this->subtotal,
            'tax' => (float) $this->tax,
            'discount' => (float) $this->discount,
            'total' => (float) $this->total,
            'notes' => $this->notes,
            'terms' => $this->terms,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'is_decided' => $this->status->isDecided(),
            'sent_at' => $this->sent_at?->toIso8601String(),
            'viewed_at' => $this->viewed_at?->toIso8601String(),
            'decided_at' => $this->decided_at?->toIso8601String(),
            'items' => ClientQuotationItemResource::collection($this->whenLoaded('items')),
            'client' => $this->whenLoaded('client', fn () => $this->client ? [
                'id' => $this->client->id,
                'name' => $this->client->full_name,
                'email' => $this->client->email,
            ] : null),
            'event' => $this->whenLoaded('event', fn () => $this->event ? [
                'id' => $this->event->id,
                'title' => $this->event->title,
            ] : null),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
