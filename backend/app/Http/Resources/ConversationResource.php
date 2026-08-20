<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Conversation
 */
class ConversationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $me = $request->user();
        $other = $me ? $this->otherParticipant($me->id) : null;

        return [
            'id' => $this->id,
            'participant' => $other ? [
                'id' => $other->id,
                'full_name' => $other->full_name,
                'avatar_url' => $other->avatar_url,
                'account_type' => $other->account_type->value,
                'account_type_label' => $other->account_type->label(),
            ] : null,
            'last_message' => $this->whenLoaded('latestMessage', fn () => $this->latestMessage ? [
                'body' => $this->latestMessage->body,
                'mine' => $this->latestMessage->sender_id === $me?->id,
                'created_at' => $this->latestMessage->created_at?->toIso8601String(),
            ] : null),
            'unread_count' => $this->unread_count ?? 0,
            'last_message_at' => $this->last_message_at?->toIso8601String(),
            'messages' => DirectMessageResource::collection($this->whenLoaded('messages')),
        ];
    }
}
