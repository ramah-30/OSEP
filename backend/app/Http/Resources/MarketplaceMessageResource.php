<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\MarketplaceMessage
 */
class MarketplaceMessageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'thread_id' => $this->thread_id,
            'sender_id' => $this->sender_id,
            'sender_name' => $this->whenLoaded('sender', fn () => $this->sender?->full_name),
            'body' => $this->body,
            'attachments' => $this->attachments ?? [],
            'quotation_id' => $this->quotation_id,
            'quotation' => $this->whenLoaded('quotation', fn () => $this->quotation ? new QuotationResource($this->quotation) : null),
            'is_read' => $this->read_at !== null,
            'read_at' => $this->read_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
