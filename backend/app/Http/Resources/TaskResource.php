<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\EventTask
 */
class TaskResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event_id' => $this->event_id,
            'title' => $this->title,
            'description' => $this->description,
            'priority' => $this->priority->value,
            'priority_label' => $this->priority->label(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'due_date' => $this->due_date?->toDateString(),
            'position' => $this->position,
            'completed_at' => $this->completed_at?->toIso8601String(),
            'assigned_to' => $this->assigned_to,
            'assignee' => $this->whenLoaded('assignee', fn () => $this->assignee ? [
                'id' => $this->assignee->id,
                'full_name' => $this->assignee->full_name,
                'avatar_url' => $this->assignee->avatar_url,
            ] : null),
            'dependencies' => $this->whenLoaded('dependencies', fn () => $this->dependencies->map(fn ($t) => [
                'id' => $t->id,
                'title' => $t->title,
                'status' => $t->status->value,
            ])),
            'comments_count' => $this->whenCounted('comments'),
            'comments' => TaskCommentResource::collection($this->whenLoaded('comments')),
        ];
    }
}
