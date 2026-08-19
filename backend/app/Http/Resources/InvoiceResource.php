<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Invoice
 */
class InvoiceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'title' => $this->title,
            'planner_id' => $this->planner_id,
            'client_id' => $this->client_id,
            'event_id' => $this->event_id,
            'client_quotation_id' => $this->client_quotation_id,
            'currency' => $this->currency,
            'issue_date' => $this->issue_date?->toDateString(),
            'due_date' => $this->due_date?->toDateString(),
            'subtotal' => (float) $this->subtotal,
            'tax' => (float) $this->tax,
            'discount' => (float) $this->discount,
            'total' => (float) $this->total,
            'amount_paid' => (float) $this->amount_paid,
            'balance' => $this->balance(),
            'payment_terms' => $this->payment_terms,
            'notes' => $this->notes,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'is_collectable' => $this->status->isCollectable(),
            'sent_at' => $this->sent_at?->toIso8601String(),
            'items' => InvoiceItemResource::collection($this->whenLoaded('items')),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'client' => $this->whenLoaded('client', fn () => $this->client ? [
                'id' => $this->client->id,
                'name' => $this->client->full_name,
                'email' => $this->client->email,
            ] : null),
            'planner' => $this->whenLoaded('planner', fn () => $this->planner ? [
                'id' => $this->planner->id,
                'name' => $this->planner->full_name,
                'phone' => $this->planner->phone,
            ] : null),
            'event' => $this->whenLoaded('event', fn () => $this->event ? [
                'id' => $this->event->id,
                'title' => $this->event->title,
            ] : null),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
