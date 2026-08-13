<?php

namespace App\Http\Resources;

use App\Services\AI\AutomationEngine;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\AiAutomationRule
 */
class AiAutomationRuleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $trigger = AutomationEngine::triggers()[$this->trigger_type] ?? [];

        return [
            'id' => $this->id,
            'name' => $this->name,
            'event_id' => $this->event_id,
            'event_title' => $this->whenLoaded('event', fn () => $this->event?->title),
            'scope' => $this->event_id ? 'event' : 'all',
            'trigger_type' => $this->trigger_type,
            'trigger_label' => $trigger['label'] ?? $this->trigger_type,
            'unit' => $trigger['unit'] ?? '',
            'threshold' => $this->threshold,
            'action_type' => $this->action_type,
            'action_label' => AutomationEngine::actions()[$this->action_type] ?? $this->action_type,
            'action_config' => $this->action_config ?? [],
            'enabled' => $this->enabled,
            'runs_count' => $this->when(isset($this->runs_count), $this->runs_count),
            'last_evaluated_at' => $this->last_evaluated_at?->toIso8601String(),
            'last_fired_at' => $this->last_fired_at?->toIso8601String(),
        ];
    }
}
