<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\EventMilestone
 */
class MilestoneResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'due_date' => $this->due_date?->toDateString(),
            'reminder_at' => $this->reminder_at?->toIso8601String(),
            'assigned_to' => $this->assigned_to,
            'assignee' => $this->whenLoaded('assignee', fn () => $this->assignee ? [
                'id' => $this->assignee->id,
                'full_name' => $this->assignee->full_name,
            ] : null),
            'position' => $this->position,
        ];
    }
}
