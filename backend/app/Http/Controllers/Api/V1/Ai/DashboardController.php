<?php

namespace App\Http\Controllers\Api\V1\Ai;

use App\Enums\RecommendationStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\AiConversationResource;
use App\Http\Resources\AiRecommendationResource;
use App\Models\AiConversation;
use App\Models\AiRecommendation;
use App\Models\Event;
use App\Services\AI\AiManager;
use App\Services\AI\OnboardingCoachService;
use App\Services\AI\RecommendationEngine;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Powers the AI Dashboard widgets: today's recommendations and conversation shortcuts —
 * an at-a-glance copilot summary across all of the planner's active events.
 */
class DashboardController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly RecommendationEngine $engine,
        private readonly AiManager $ai,
        private readonly OnboardingCoachService $coach,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $activeEvents = Event::where('planner_id', $user->id)
            ->whereNotIn('status', ['archived', 'cancelled', 'completed'])
            ->orderBy('event_date')
            ->get();

        foreach ($activeEvents as $event) {
            $this->engine->syncForEvent($user, $event);
        }

        $recommendations = AiRecommendation::where('user_id', $user->id)
            ->where('status', RecommendationStatus::Pending->value)
            ->with('event:id,title')
            ->orderByRaw("FIELD(priority, 'critical', 'high', 'medium', 'low')")
            ->orderByDesc('confidence')
            ->limit(6)
            ->get();

        $conversations = AiConversation::where('user_id', $user->id)
            ->with(['event:id,title', 'latestMessage'])
            ->orderByDesc('pinned')
            ->orderByDesc('last_message_at')
            ->limit(5)
            ->get();

        return $this->success([
            'assistant_name' => config('ai.assistant_name', 'OSEP AI'),
            'driver' => $this->ai->driver(),
            'is_live' => $this->ai->isLive(),
            'onboarding' => $this->coach->for($user),
            'stats' => [
                'active_events' => $activeEvents->count(),
                'open_recommendations' => AiRecommendation::where('user_id', $user->id)
                    ->where('status', RecommendationStatus::Pending->value)->count(),
                'conversations' => AiConversation::where('user_id', $user->id)->count(),
            ],
            'recommendations' => AiRecommendationResource::collection($recommendations),
            'conversations' => AiConversationResource::collection($conversations),
        ]);
    }
}
