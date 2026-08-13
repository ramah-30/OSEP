<?php

namespace App\Http\Controllers\Api\V1\Ai;

use App\Enums\RecommendationStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\AiRecommendationResource;
use App\Models\AiRecommendation;
use App\Models\Event;
use App\Services\AI\RecommendationEngine;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The continuously-refreshed recommendation feed. Opening it re-analyses the
 * planner's active events so the cards always reflect the current data.
 */
class RecommendationController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly RecommendationEngine $engine) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $eventId = $request->integer('event_id') ?: null;

        $events = Event::where('planner_id', $user->id)
            ->whereNotIn('status', ['archived', 'cancelled', 'completed'])
            ->when($eventId, fn ($q) => $q->whereKey($eventId))
            ->get();

        foreach ($events as $event) {
            $this->engine->syncForEvent($user, $event);
        }

        $recommendations = AiRecommendation::where('user_id', $user->id)
            ->where('status', RecommendationStatus::Pending->value)
            ->when($eventId, fn ($q) => $q->where('event_id', $eventId))
            ->with('event:id,title')
            ->orderByRaw("FIELD(priority, 'critical', 'high', 'medium', 'low')")
            ->orderByDesc('confidence')
            ->get();

        return $this->success([
            'recommendations' => AiRecommendationResource::collection($recommendations),
            'counts' => [
                'total' => $recommendations->count(),
                'critical' => $recommendations->filter(fn ($r) => $r->priority->value === 'critical')->count(),
                'high' => $recommendations->filter(fn ($r) => $r->priority->value === 'high')->count(),
            ],
        ]);
    }

    public function dismiss(Request $request, AiRecommendation $recommendation): JsonResponse
    {
        $this->authorize($request, $recommendation);

        $recommendation->update([
            'status' => RecommendationStatus::Dismissed->value,
            'resolved_at' => now(),
        ]);

        return $this->success(null, 'Recommendation dismissed.');
    }

    public function apply(Request $request, AiRecommendation $recommendation): JsonResponse
    {
        $this->authorize($request, $recommendation);

        $recommendation->update([
            'status' => RecommendationStatus::Accepted->value,
            'resolved_at' => now(),
        ]);

        return $this->success([
            'action_type' => $recommendation->action_type,
            'action_payload' => $recommendation->action_payload,
            'event_id' => $recommendation->event_id,
        ], 'Recommendation accepted.');
    }

    private function authorize(Request $request, AiRecommendation $recommendation): void
    {
        abort_unless($recommendation->user_id === $request->user()->id, 404);
    }
}
