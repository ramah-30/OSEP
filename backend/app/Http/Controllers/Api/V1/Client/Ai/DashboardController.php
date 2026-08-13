<?php

namespace App\Http\Controllers\Api\V1\Client\Ai;

use App\Http\Controllers\Controller;
use App\Http\Resources\AiConversationResource;
use App\Models\AiConversation;
use App\Services\AI\AiManager;
use App\Services\AI\Client\ClientContextBuilder;
use App\Services\AI\Client\ClientReminderEngine;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The client AI concierge home: their event at a glance, the concierge's
 * prioritised reminders (approvals, payments, RSVPs) and recent conversations.
 */
class DashboardController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly ClientContextBuilder $contextBuilder,
        private readonly ClientReminderEngine $reminders,
        private readonly AiManager $ai,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $client = $request->user();
        $context = $this->contextBuilder->forClient($client);

        $conversations = AiConversation::where('user_id', $client->id)
            ->with('latestMessage')
            ->orderByDesc('pinned')
            ->orderByDesc('last_message_at')
            ->limit(5)
            ->get();

        $reminders = $this->reminders->fromContext($context);

        $event = $context['event'] ?? null;
        $guests = $context['guests'] ?? [];
        $approvals = $context['approvals'] ?? [];
        $finance = $context['finance'] ?? [];

        return $this->success([
            'assistant_name' => config('ai.client_assistant_name', 'OSEP Planning Concierge'),
            'driver' => $this->ai->driver(),
            'is_live' => $this->ai->isLive(),
            'event' => $event,
            'stats' => [
                'progress' => $event['progress'] ?? null,
                'days_until' => $event['days_until'] ?? null,
                'guests_confirmed' => $guests['confirmed'] ?? 0,
                'guests_total' => $guests['total'] ?? 0,
                'approvals_pending' => $approvals['pending'] ?? 0,
                'outstanding_amount' => $finance['outstanding_amount'] ?? 0,
                'reminders' => count($reminders),
            ],
            'reminders' => $reminders,
            'conversations' => AiConversationResource::collection($conversations),
        ]);
    }
}
