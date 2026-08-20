<?php

namespace App\Http\Controllers\Api\V1\Ai;

use App\Http\Controllers\Controller;
use App\Http\Resources\AiConversationResource;
use App\Http\Resources\AiMessageResource;
use App\Models\AiConversation;
use App\Models\Event;
use App\Services\AI\AiManager;
use App\Services\AI\Orchestrator;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The planner's AI conversation workspace: threads, messages and the single
 * chat entry point. Every conversation is scoped to the authenticated planner.
 */
class ChatController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly Orchestrator $orchestrator,
        private readonly AiManager $ai,
    ) {}

    /** All of the planner's conversations, pinned first, newest activity next. */
    public function index(Request $request): JsonResponse
    {
        $conversations = AiConversation::where('user_id', $request->user()->id)
            ->with(['event:id,title', 'latestMessage'])
            ->withCount('messages')
            ->orderByDesc('pinned')
            ->orderByDesc('last_message_at')
            ->orderByDesc('created_at')
            ->get();

        return $this->success([
            'conversations' => AiConversationResource::collection($conversations),
        ]);
    }

    /** Workspace metadata: active driver, suggested prompts, events for context. */
    public function meta(Request $request): JsonResponse
    {
        $events = Event::where('planner_id', $request->user()->id)
            ->orderByDesc('event_date')
            ->get(['id', 'title', 'event_type', 'event_date', 'status']);

        return $this->success([
            'assistant_name' => config('ai.assistant_name', 'OSEP AI'),
            'driver' => $this->ai->driver(),
            'is_live' => $this->ai->isLive(),
            'events' => $events->map(fn ($e) => [
                'id' => $e->id,
                'title' => $e->title,
                'type' => $e->event_type,
                'date' => $e->event_date?->toFormattedDateString(),
                'status' => $e->status?->value,
            ]),
            'suggested_prompts' => [
                'Summarize where this event stands',
                'Is the budget on track?',
                'What tasks are overdue?',
                'How are RSVPs looking?',
                'Which vendors still need attention?',
                'What are the biggest risks right now?',
            ],
        ]);
    }

    /** Full thread with its messages. */
    public function show(Request $request, AiConversation $conversation): JsonResponse
    {
        $this->authorizeConversation($request, $conversation);
        $userId = $request->user()->id;
        $conversation->load([
            'event:id,title',
            'messages.feedback' => fn ($q) => $q->where('user_id', $userId),
            'messages.action.event:id,title',
        ]);

        return $this->success([
            'conversation' => new AiConversationResource($conversation),
        ]);
    }

    /**
     * Send a message. Creates the conversation on the fly when no id is given,
     * so the client can open a fresh thread and talk in a single call.
     */
    public function chat(Request $request): JsonResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
            'conversation_id' => ['nullable', 'integer'],
            'event_id' => ['nullable', 'integer'],
            'context_type' => ['nullable', 'in:general,event,budget,vendor'],
        ]);

        $user = $request->user();

        $eventId = $this->resolveEventId($request, $data['event_id'] ?? null);

        if (! empty($data['conversation_id'])) {
            $conversation = AiConversation::where('user_id', $user->id)->findOrFail($data['conversation_id']);
        } else {
            $conversation = AiConversation::create([
                'user_id' => $user->id,
                'event_id' => $eventId,
                'context_type' => $data['context_type'] ?? ($eventId ? 'event' : 'general'),
            ]);
        }

        $assistant = $this->orchestrator->chat($user, $conversation, $data['message']);

        $conversation->load(['event:id,title']);

        return $this->success([
            'conversation' => new AiConversationResource($conversation),
            'message' => new AiMessageResource($assistant),
        ]);
    }

    /** Rename, pin/unpin or refile a conversation. */
    public function update(Request $request, AiConversation $conversation): JsonResponse
    {
        $this->authorizeConversation($request, $conversation);

        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:120'],
            'pinned' => ['sometimes', 'boolean'],
            'folder' => ['sometimes', 'nullable', 'string', 'max:60'],
        ]);

        $conversation->update($data);

        return $this->success([
            'conversation' => new AiConversationResource($conversation->fresh(['event:id,title'])),
        ], 'Conversation updated.');
    }

    public function destroy(Request $request, AiConversation $conversation): JsonResponse
    {
        $this->authorizeConversation($request, $conversation);
        $conversation->delete();

        return $this->success(null, 'Conversation deleted.');
    }

    private function authorizeConversation(Request $request, AiConversation $conversation): void
    {
        abort_unless($conversation->user_id === $request->user()->id, 404);
    }

    /** Ensure any event_id supplied really belongs to the planner. */
    private function resolveEventId(Request $request, ?int $eventId): ?int
    {
        if (! $eventId) {
            return null;
        }

        return Event::where('planner_id', $request->user()->id)
            ->whereKey($eventId)
            ->value('id');
    }
}
