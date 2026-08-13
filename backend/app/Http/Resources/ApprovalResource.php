<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Approval
 */
class ApprovalResource extends JsonResource
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
            'type' => $this->type,
            'description' => $this->description,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'client_note' => $this->client_note,
            'decided_at' => $this->decided_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'history' => ApprovalHistoryResource::collection($this->whenLoaded('history')),
        ];
    }
}
