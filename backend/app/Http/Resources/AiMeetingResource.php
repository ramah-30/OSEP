<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\AiMeeting
 */
class AiMeetingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'meeting_type' => $this->meeting_type,
            'meeting_date' => $this->meeting_date?->toDateString(),
            'attendees' => $this->attendees ?? [],
            'notes' => $this->notes,
            'summary' => $this->summary,
            'status' => $this->status,
            'model' => $this->model,
            'grounded' => (bool) ($this->meta['grounded'] ?? false),
            'event_id' => $this->event_id,
            'event_title' => $this->whenLoaded('event', fn () => $this->event?->title),
            'scope' => $this->event_id ? 'event' : 'general',
            'action_items' => AiMeetingActionItemResource::collection($this->whenLoaded('actionItems')),
            'action_items_count' => $this->when(isset($this->action_items_count), fn () => $this->action_items_count),
            'open_actions_count' => $this->when(isset($this->open_actions_count), fn () => $this->open_actions_count),
            'processed_at' => $this->processed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
