<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\AiTemplate
 */
class AiTemplateResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'category' => $this->category,
            'name' => $this->name,
            'description' => $this->description,
            'icon' => $this->icon,
            'output_format' => $this->output_format,
            'variables' => $this->variables ?? [],
            'requires_event' => $this->requires_event,
            'is_system' => $this->is_system,
        ];
    }
}
