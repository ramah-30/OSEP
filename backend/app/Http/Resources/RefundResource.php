<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Refund
 */
class RefundResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'refund_number' => $this->refund_number,
            'payment_id' => $this->payment_id,
            'invoice_id' => $this->invoice_id,
            'event_id' => $this->event_id,
            'client_id' => $this->client_id,
            'reason' => $this->reason,
            'amount' => (float) $this->amount,
            'currency' => $this->currency,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'approved_at' => $this->approved_at?->toIso8601String(),
            'processed_at' => $this->processed_at?->toIso8601String(),
            'notes' => $this->notes,
            'payment' => $this->whenLoaded('payment', fn () => $this->payment ? [
                'id' => $this->payment->id,
                'payment_number' => $this->payment->payment_number,
            ] : null),
            'event' => $this->whenLoaded('event', fn () => $this->event ? [
                'id' => $this->event->id,
                'title' => $this->event->title,
            ] : null),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
