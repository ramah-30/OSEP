<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Quotation
 */
class QuotationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'booking_request_id' => $this->booking_request_id,
            'planner_id' => $this->planner_id,
            'planner_name' => $this->whenLoaded('planner', fn () => $this->planner?->full_name),
            'provider_type' => $this->providerType(),
            'provider_name' => $this->providerName(),
            'vendor_id' => $this->vendor_id,
            'venue_id' => $this->venue_id,
            'event_id' => $this->event_id,
            'subtotal' => (float) $this->subtotal,
            'tax' => (float) $this->tax,
            'total' => (float) $this->total,
            'currency' => $this->currency,
            'timeline' => $this->timeline,
            'terms' => $this->terms,
            'notes' => $this->notes,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'is_actionable' => $this->status->isActionable(),
            'expires_at' => $this->expires_at?->toDateString(),
            'sent_at' => $this->sent_at?->toIso8601String(),
            'decided_at' => $this->decided_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'items' => QuotationItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
