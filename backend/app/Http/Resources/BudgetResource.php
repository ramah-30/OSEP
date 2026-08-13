<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Budget
 */
class BudgetResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event_id' => $this->event_id,
            'currency' => $this->currency,
            'estimated_total' => (float) $this->estimated_total,
            'revised_total' => $this->revised_total !== null ? (float) $this->revised_total : null,
            'final_total' => $this->final_total !== null ? (float) $this->final_total : null,
            'active_total' => $this->activeTotal(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'is_editable' => $this->status->isEditable(),
            'notes' => $this->notes,
            'approved_at' => $this->approved_at?->toIso8601String(),
            'locked_at' => $this->locked_at?->toIso8601String(),
            'items' => BudgetItemResource::collection($this->whenLoaded('items')),
            'event' => $this->whenLoaded('event', fn () => [
                'id' => $this->event->id,
                'title' => $this->event->title,
            ]),
        ];
    }
}
