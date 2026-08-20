<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\TaxRule
 */
class TaxRuleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'kind' => $this->kind,
            'type' => $this->type,
            'rate' => (float) $this->rate,
            'is_default' => $this->is_default,
            'is_active' => $this->is_active,
        ];
    }
}
