<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\VendorPortfolio
 */
class VendorPortfolioResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'vendor_id' => $this->vendor_id,
            'title' => $this->title,
            'description' => $this->description,
            'event_type' => $this->event_type,
            'event_date' => $this->event_date?->toDateString(),
            'cover_url' => $this->cover_url,
            'media' => $this->media ?? [],
            'client_feedback' => $this->client_feedback,
            'is_case_study' => $this->is_case_study,
            'sort_order' => $this->sort_order,
        ];
    }
}
