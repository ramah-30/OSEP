<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Review
 */
class ReviewResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reviewer_id' => $this->reviewer_id,
            'reviewer_name' => $this->whenLoaded('reviewer', fn () => $this->reviewer?->full_name),
            'provider_type' => $this->providerType(),
            'provider_name' => $this->providerName(),
            'vendor_id' => $this->vendor_id,
            'venue_id' => $this->venue_id,
            'event_id' => $this->event_id,
            'ratings' => [
                'professionalism' => $this->rating_professionalism,
                'communication' => $this->rating_communication,
                'quality' => $this->rating_quality,
                'value' => $this->rating_value,
                'timeliness' => $this->rating_timeliness,
            ],
            'overall_rating' => (float) $this->overall_rating,
            'title' => $this->title,
            'comment' => $this->comment,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'created_at' => $this->created_at?->toIso8601String(),
            'replies' => ReviewReplyResource::collection($this->whenLoaded('replies')),
        ];
    }
}
