<?php

namespace App\Http\Controllers\Api\V1\Client\Ai;

use App\Http\Controllers\Controller;
use App\Http\Resources\AiConversationResource;
use App\Http\Resources\AiMessageResource;
use App\Models\AiConversation;
use App\Services\AI\AiManager;
use App\Services\AI\Client\ClientOrchestrator;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The client's AI concierge conversation workspace: threads, messages and the
 * single chat entry point. Every conversation is scoped to the authenticated
 * client.
 */
class ChatController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly ClientOrchestrator $orchestrator,
        private readonly AiManager $ai,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $conversations = AiConversation::where('user_id', $request->user()->id)
            ->with('latestMessage')
            ->withCount('messages')
            ->orderByDesc('pinned')
            ->orderByDesc('last_message_at')
            ->orderByDesc('created_at')
            ->get();

        return $this->success([
            'conversations' => AiConversationResource::collection($conversations),
        ]);
    }

    public function meta(Request $request): JsonResponse
    {
        $user = $request->user();

        $events = \App\Models\Event::where(‘client_id’, $user->id)
            ->where(‘status’, ‘!=’, ‘cancelled’)
            ->orderBy(‘event_date’, ‘desc’)
            ->select(‘id’, ‘title’, ‘event_date’, ‘status’)
            ->get()
            ->map(fn ($e) => [
                ‘id’ => $e->id,
                ‘title’ => $e->title,
                ‘date’ => $e->event_date?->format(‘M d, Y’),
                ‘status’ => $e->status,
            ]);

        return $this->success([
            ‘assistant_name’ => config(‘ai.client_assistant_name’, ‘OSEP Planning Concierge’),
            ‘driver’ => $this->ai->driver(),
            ‘is_live’ => $this->ai->isLive(),
            ‘events’ => $events,
            ‘suggested_prompts’ => [
                ‘Find me a planner’,
                ‘Show my progress summary’,
                ‘How many guests have confirmed?’,
                ‘What’s my outstanding balance?’,
                ‘What should I take care of next?’,
            ],
        ]);
    }

    public function show(Request $request, AiConversation $conversation): JsonResponse
    {
        $this->authorizeConversation($request, $conversation);
        $conversation->load('messages.action');

        return $this->success([
            'conversation' => new AiConversationResource($conversation),
        ]);
    }

    public function chat(Request $request): JsonResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
            'conversation_id' => ['nullable', 'integer'],
            'event_id' => ['nullable', 'integer'],
        ]);

        $user = $request->user();

        if (! empty($data['conversation_id'])) {
            $conversation = AiConversation::where('user_id', $user->id)->findOrFail($data['conversation_id']);
        } else {
            $conversation = AiConversation::create([
                'user_id' => $user->id,
                'context_type' => 'event',
                'event_id' => $data['event_id'] ?? null,
            ]);
        }

        $assistant = $this->orchestrator->chat($user, $conversation, $data['message']);

        return $this->success([
            'conversation' => new AiConversationResource($conversation->fresh()),
            'message' => new AiMessageResource($assistant),
        ]);
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
}
