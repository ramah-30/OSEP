<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\PaymentSchedule
 */
class PaymentScheduleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event_id' => $this->event_id,
            'invoice_id' => $this->invoice_id,
            'vendor_assigned_id' => $this->vendor_assigned_id,
            'label' => $this->label,
            'percentage' => $this->percentage !== null ? (float) $this->percentage : null,
            'amount' => (float) $this->amount,
            'currency' => $this->currency,
            'due_date' => $this->due_date?->toDateString(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'paid_at' => $this->paid_at?->toDateString(),
            'sort_order' => $this->sort_order,
            'invoice' => $this->whenLoaded('invoice', fn () => $this->invoice ? [
                'id' => $this->invoice->id,
                'invoice_number' => $this->invoice->invoice_number,
            ] : null),
        ];
    }
}
