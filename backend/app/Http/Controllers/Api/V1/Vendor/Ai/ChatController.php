<?php

namespace App\Http\Controllers\Api\V1\Vendor\Ai;

use App\Http\Controllers\Controller;
use App\Http\Resources\AiConversationResource;
use App\Http\Resources\AiMessageResource;
use App\Models\AiConversation;
use App\Services\AI\AiManager;
use App\Services\AI\Vendor\VendorOrchestrator;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The vendor's AI conversation workspace: threads, messages and the single chat
 * entry point. Every conversation is scoped to the authenticated vendor.
 */
class ChatController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly VendorOrchestrator $orchestrator,
        private readonly AiManager $ai,
    ) {}

    /** All of the vendor's conversations, newest activity first. */
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

    /** Workspace metadata: active engine and suggested prompts. */
    public function meta(Request $request): JsonResponse
    {
        return $this->success([
            'assistant_name' => config('ai.vendor_assistant_name', 'OSEP Vendor Copilot'),
            'driver' => $this->ai->driver(),
            'is_live' => $this->ai->isLive(),
            'suggested_prompts' => [
                'How’s my business doing?',
                'Which requests need a reply?',
                'What’s my quotation win rate?',
                'How much revenue have I booked?',
                'How’s my rating looking?',
                'What should I focus on today?',
            ],
        ]);
    }

    /** Full thread with its messages. */
    public function show(Request $request, AiConversation $conversation): JsonResponse
    {
        $this->authorizeConversation($request, $conversation);
        $conversation->load('messages.action');

        return $this->success([
            'conversation' => new AiConversationResource($conversation),
        ]);
    }

    /** Send a message, creating the thread on the fly when no id is given. */
    public function chat(Request $request): JsonResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
            'conversation_id' => ['nullable', 'integer'],
        ]);

        $user = $request->user();

        if (! empty($data['conversation_id'])) {
            $conversation = AiConversation::where('user_id', $user->id)->findOrFail($data['conversation_id']);
        } else {
            $conversation = AiConversation::create([
                'user_id' => $user->id,
                'context_type' => 'general',
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
