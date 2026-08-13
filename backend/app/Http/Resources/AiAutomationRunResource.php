<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\AiAutomationRun
 */
class AiAutomationRunResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'rule_id' => $this->ai_automation_rule_id,
            'rule_name' => $this->whenLoaded('rule', fn () => $this->rule?->name),
            'event_id' => $this->event_id,
            'event_title' => $this->whenLoaded('event', fn () => $this->event?->title),
            'summary' => $this->summary,
            'action_type' => $this->action_type,
            'result_type' => $this->result_type,
            'result_id' => $this->result_id,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
