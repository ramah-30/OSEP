<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\GuestCategory
 */
class GuestCategoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'color' => $this->color,
            'priority' => $this->priority,
            'default_seating_area' => $this->default_seating_area,
            'is_default' => $this->is_default,
            'is_owned' => $this->created_by !== null,
        ];
    }
}
