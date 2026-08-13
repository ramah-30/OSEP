<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\DirectMessageResource;
use App\Models\Conversation;
use App\Models\DirectMessage;
use App\Models\Notification;
use App\Models\User;
use App\Services\MessagingService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Unified 1:1 direct messaging for every role. Access is governed by
 * MessagingService (planner-as-hub) and every conversation is the single
 * canonical thread for its pair, so messages can never cross wires.
 */
class ConversationController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly MessagingService $messaging) {}

    /** The current user's conversations, newest activity first. */
    public function index(Request $request): JsonResponse
    {
        $me = $request->user();

        $conversations = Conversation::forUser($me->id)
            ->has('messages')
            ->with(['userOne', 'userTwo', 'latestMessage'])
            ->withCount(['messages as unread_count' => fn ($q) => $q
                ->whereNull('read_at')->where('sender_id', '!=', $me->id)])
            ->orderByDesc('last_message_at')
            ->get();

        return $this->success([
            'conversations' => ConversationResource::collection($conversations),
        ]);
    }

    /** People the current user is allowed to start a conversation with. */
    public function contacts(Request $request): JsonResponse
    {
        $contacts = $this->messaging->contactsFor($request->user())->map(fn (User $u) => [
            'id' => $u->id,
            'full_name' => $u->full_name,
            'avatar_url' => $u->avatar_url,
            'account_type' => $u->account_type->value,
            'account_type_label' => $u->account_type->label(),
        ]);

        return $this->success(['contacts' => $contacts->values()]);
    }

    /** Open (or reuse) the thread with a given user, optionally sending a first message. */
    public function start(Request $request): JsonResponse
    {
        $data = $request->validate([
            'recipient_id' => ['required', 'integer', 'exists:users,id'],
            'body' => ['nullable', 'string', 'max:5000'],
        ]);

        $me = $request->user();
        $other = User::findOrFail($data['recipient_id']);

        $conversation = $this->messaging->openConversation($me, $other);

        if (! $conversation) {
            return $this->error('You are not allowed to message this user.', 403);
        }

        if (! empty($data['body'])) {
            $this->appendMessage($conversation, $me, $data['body']);
        }

        $conversation->load(['userOne', 'userTwo', 'latestMessage']);

        return $this->success([
            'conversation' => new ConversationResource($conversation),
        ]);
    }

    /** The full thread, with its messages; marks the other party's messages read. */
    public function show(Request $request, Conversation $conversation): JsonResponse
    {
        $me = $request->user();
        abort_unless($conversation->hasParticipant($me->id), 404);

        $conversation->messages()->whereNull('read_at')
            ->where('sender_id', '!=', $me->id)
            ->update(['read_at' => now()]);

        $conversation->load(['userOne', 'userTwo', 'messages']);

        return $this->success([
            'conversation' => new ConversationResource($conversation),
        ]);
    }

    public function send(Request $request, Conversation $conversation): JsonResponse
    {
        $me = $request->user();
        abort_unless($conversation->hasParticipant($me->id), 404);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $message = $this->appendMessage($conversation, $me, $data['body']);

        return $this->created([
            'message' => new DirectMessageResource($message),
        ], 'Message sent.');
    }

    private function appendMessage(Conversation $conversation, User $sender, string $body): DirectMessage
    {
        $message = $conversation->messages()->create([
            'sender_id' => $sender->id,
            'body' => $body,
        ]);

        $conversation->update(['last_message_at' => now()]);
        $this->notifyCounterparty($conversation, $sender);

        return $message;
    }

    private function notifyCounterparty(Conversation $conversation, User $sender): void
    {
        $recipientId = $conversation->user_one_id === $sender->id
            ? $conversation->user_two_id
            : $conversation->user_one_id;

        Notification::create([
            'user_id' => $recipientId,
            'type' => 'message',
            'title' => 'New message',
            'message' => "{$sender->full_name} sent you a message.",
            'data' => ['conversation_id' => $conversation->id],
        ]);
    }
}
