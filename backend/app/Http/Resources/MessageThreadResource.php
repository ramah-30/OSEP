<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\MessageThread
 */
class MessageThreadResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $viewer = $request->user();

        return [
            'id' => $this->id,
            'subject' => $this->subject,
            'planner_id' => $this->planner_id,
            'planner_name' => $this->whenLoaded('planner', fn () => $this->planner?->full_name),
            'vendor_id' => $this->vendor_id,
            'venue_id' => $this->venue_id,
            'provider_type' => $this->providerType(),
            'provider_name' => $this->providerName(),
            'event_id' => $this->event_id,
            'event_title' => $this->whenLoaded('event', fn () => $this->event?->title),
            'last_message_at' => $this->last_message_at?->toIso8601String(),
            'unread_count' => $this->when(
                isset($this->unread_count),
                fn () => (int) $this->unread_count,
            ),
            'counterparty' => $this->counterpartyFor($viewer),
            'messages' => MarketplaceMessageResource::collection($this->whenLoaded('messages')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    /**
     * The name shown in the inbox: the *other* participant relative to the
     * viewer (planner sees the vendor/venue, vendor sees the planner).
     */
    private function counterpartyFor(?object $viewer): ?string
    {
        if ($viewer && $viewer->id === $this->planner_id) {
            return $this->providerName();
        }

        return $this->planner?->full_name;
    }
}
