<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Receipt
 */
class ReceiptResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'receipt_number' => $this->receipt_number,
            'payment_id' => $this->payment_id,
            'client_id' => $this->client_id,
            'event_id' => $this->event_id,
            'invoice_id' => $this->invoice_id,
            'amount' => (float) $this->amount,
            'currency' => $this->currency,
            'issued_at' => $this->issued_at?->toIso8601String(),
            'payment' => $this->whenLoaded('payment', fn () => $this->payment ? [
                'id' => $this->payment->id,
                'payment_number' => $this->payment->payment_number,
                'method_label' => $this->payment->method->label(),
                'reference' => $this->payment->reference,
            ] : null),
            'client' => $this->whenLoaded('client', fn () => $this->client ? [
                'id' => $this->client->id,
                'name' => $this->client->full_name,
            ] : null),
            'event' => $this->whenLoaded('event', fn () => $this->event ? [
                'id' => $this->event->id,
                'title' => $this->event->title,
            ] : null),
        ];
    }
}
