<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Payment
 */
class PaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payment_number' => $this->payment_number,
            'event_id' => $this->event_id,
            'invoice_id' => $this->invoice_id,
            'vendor_assigned_id' => $this->vendor_assigned_id,
            'payment_schedule_id' => $this->payment_schedule_id,
            'direction' => $this->direction->value,
            'direction_label' => $this->direction->label(),
            'party_name' => $this->party_name,
            'method' => $this->method->value,
            'method_label' => $this->method->label(),
            'amount' => (float) $this->amount,
            'currency' => $this->currency,
            'transaction_ref' => $this->transaction_ref,
            'reference' => $this->reference,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'paid_at' => $this->paid_at?->toDateString(),
            'notes' => $this->notes,
            'invoice' => $this->whenLoaded('invoice', fn () => $this->invoice ? [
                'id' => $this->invoice->id,
                'invoice_number' => $this->invoice->invoice_number,
            ] : null),
            'event' => $this->whenLoaded('event', fn () => $this->event ? [
                'id' => $this->event->id,
                'title' => $this->event->title,
            ] : null),
            'vendor' => $this->whenLoaded('vendorAssignment', fn () => $this->vendorAssignment ? [
                'id' => $this->vendorAssignment->id,
                'name' => $this->vendorAssignment->vendor_name,
            ] : null),
            'receipt' => $this->whenLoaded('receipt', fn () => $this->receipt ? [
                'id' => $this->receipt->id,
                'receipt_number' => $this->receipt->receipt_number,
            ] : null),
        ];
    }
}
