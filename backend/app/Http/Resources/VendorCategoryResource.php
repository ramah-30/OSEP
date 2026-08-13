<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\VendorCategory
 */
class VendorCategoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'icon' => $this->icon,
            'description' => $this->description,
            'is_custom' => $this->is_custom,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'vendors_count' => $this->whenCounted('vendors'),
        ];
    }
}
