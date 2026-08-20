<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\BudgetItem
 */
class BudgetItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event_id' => $this->event_id,
            'budget_id' => $this->budget_id,
            'vendor_assigned_id' => $this->vendor_assigned_id,
            'category' => $this->category,
            'description' => $this->description,
            'estimated_cost' => (float) $this->estimated_cost,
            'approved_cost' => (float) $this->approved_cost,
            'actual_cost' => (float) $this->actual_cost,
            'quantity' => (float) $this->quantity,
            'unit_cost' => (float) $this->unit_cost,
            'tax' => (float) $this->tax,
            'discount' => (float) $this->discount,
            'variance' => (float) $this->actual_cost - (float) $this->estimated_cost,
            'notes' => $this->notes,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'vendor_assignment' => $this->whenLoaded('vendorAssignment', fn () => $this->vendorAssignment ? [
                'id' => $this->vendorAssignment->id,
                'vendor_name' => $this->vendorAssignment->vendor_name,
            ] : null),
        ];
    }
}
