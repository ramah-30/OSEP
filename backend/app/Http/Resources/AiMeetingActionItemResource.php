<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\AiMeetingActionItem
 */
class AiMeetingActionItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'description' => $this->description,
            'owner' => $this->owner,
            'due_date' => $this->due_date?->toDateString(),
            'status' => $this->status,
            'task_id' => $this->task_id,
            'converted' => $this->task_id !== null,
        ];
    }
}
