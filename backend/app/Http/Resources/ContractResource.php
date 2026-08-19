<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Contract
 */
class ContractResource extends JsonResource
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
            'quotation_id' => $this->quotation_id,
            'booking_request_id' => $this->booking_request_id,
            'planner_id' => $this->planner_id,
            'planner_name' => $this->whenLoaded('planner', fn () => $this->planner?->full_name),
            'provider_type' => $this->providerType(),
            'provider_name' => $this->providerName(),
            'vendor_id' => $this->vendor_id,
            'venue_id' => $this->venue_id,
            'event_id' => $this->event_id,
            'event_title' => $this->whenLoaded('event', fn () => $this->event?->title),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'is_open' => $this->status->isOpen(),
            'amount' => $this->amount !== null ? (float) $this->amount : null,
            'amount_paid' => (float) $this->amount_paid,
            'balance' => $this->balance(),
            'payment_status' => $this->payment_status->value,
            'payment_status_label' => $this->payment_status->label(),
            'provider_phone' => $this->providerPhone(),
            'currency' => $this->currency,
            'terms' => $this->terms,
            'document_path' => $this->document_path,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'signed_at' => $this->signed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
