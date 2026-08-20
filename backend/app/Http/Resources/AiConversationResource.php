<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\AiConversation
 */
class AiConversationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'context_type' => $this->context_type,
            'folder' => $this->folder,
            'pinned' => $this->pinned,
            'event_id' => $this->event_id,
            'event_title' => $this->whenLoaded('event', fn () => $this->event?->title),
            'last_message_at' => $this->last_message_at?->toIso8601String(),
            'message_count' => $this->when(isset($this->messages_count), $this->messages_count),
            'last_message' => $this->whenLoaded('latestMessage', fn () => $this->latestMessage ? [
                'role' => $this->latestMessage->role,
                'preview' => \Illuminate\Support\Str::limit($this->latestMessage->content, 90),
            ] : null),
            'messages' => AiMessageResource::collection($this->whenLoaded('messages')),
        ];
    }
}
