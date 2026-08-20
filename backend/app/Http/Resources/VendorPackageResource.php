<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\VendorPackage
 */
class VendorPackageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'vendor_id' => $this->vendor_id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price !== null ? (float) $this->price : null,
            'currency' => $this->currency,
            'price_unit' => $this->price_unit,
            'inclusions' => $this->inclusions ?? [],
            'addons' => $this->addons ?? [],
            'terms' => $this->terms,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
        ];
    }
}
