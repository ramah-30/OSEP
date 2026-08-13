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
use App\Services\AI\HealthScoreService;
use App\Services\AI\OnboardingCoachService;
use App\Services\AI\RecommendationEngine;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Powers the AI Dashboard widgets: today's recommendations, portfolio health,
 * a forecast panel and conversation shortcuts — an at-a-glance copilot summary
 * across all of the planner's active events.
 */
class DashboardController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly RecommendationEngine $engine,
        private readonly HealthScoreService $health,
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

        $healthRows = [];
        foreach ($activeEvents as $event) {
            $this->engine->syncForEvent($user, $event);
            $score = $this->health->for($user, $event);
            if ($score) {
                $healthRows[] = [
                    'event_id' => $event->id,
                    'event_title' => $event->title,
                    'score' => $score->score,
                    'label' => $score->label,
                    'date' => $event->event_date?->toFormattedDateString(),
                ];
            }
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

        // Forecast panel: nearest upcoming event with data.
        $forecasts = [];
        $forecastEvent = null;
        foreach ($healthRows as $row) {
            $score = $this->health->for($user, $activeEvents->firstWhere('id', $row['event_id']));
            if ($score && ! empty($score->forecasts)) {
                $forecasts = $score->forecasts;
                $forecastEvent = ['id' => $row['event_id'], 'title' => $row['event_title']];
                break;
            }
        }

        $avgHealth = count($healthRows)
            ? (int) round(collect($healthRows)->avg('score'))
            : null;

        return $this->success([
            'assistant_name' => config('ai.assistant_name', 'OSEP AI'),
            'driver' => $this->ai->driver(),
            'is_live' => $this->ai->isLive(),
            'onboarding' => $this->coach->for($user),
            'stats' => [
                'active_events' => $activeEvents->count(),
                'open_recommendations' => AiRecommendation::where('user_id', $user->id)
                    ->where('status', RecommendationStatus::Pending->value)->count(),
                'avg_health' => $avgHealth,
                'conversations' => AiConversation::where('user_id', $user->id)->count(),
            ],
            'health' => collect($healthRows)->sortBy('score')->values(),
            'recommendations' => AiRecommendationResource::collection($recommendations),
            'forecast' => ['event' => $forecastEvent, 'items' => $forecasts],
            'conversations' => AiConversationResource::collection($conversations),
        ]);
    }
}
