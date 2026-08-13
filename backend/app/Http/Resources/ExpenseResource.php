<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Expense
 */
class ExpenseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'expense_number' => $this->expense_number,
            'event_id' => $this->event_id,
            'vendor_assigned_id' => $this->vendor_assigned_id,
            'budget_item_id' => $this->budget_item_id,
            'category' => $this->category,
            'description' => $this->description,
            'amount' => (float) $this->amount,
            'tax' => (float) $this->tax,
            'total' => (float) $this->total,
            'currency' => $this->currency,
            'payment_method' => $this->payment_method?->value,
            'payment_method_label' => $this->payment_method?->label(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'expense_date' => $this->expense_date?->toDateString(),
            'receipt_path' => $this->receipt_path,
            'notes' => $this->notes,
            'approved_at' => $this->approved_at?->toIso8601String(),
            'rejected_reason' => $this->rejected_reason,
            'event' => $this->whenLoaded('event', fn () => [
                'id' => $this->event->id,
                'title' => $this->event->title,
            ]),
            'vendor' => $this->whenLoaded('vendorAssignment', fn () => $this->vendorAssignment ? [
                'id' => $this->vendorAssignment->id,
                'name' => $this->vendorAssignment->vendor_name,
            ] : null),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
