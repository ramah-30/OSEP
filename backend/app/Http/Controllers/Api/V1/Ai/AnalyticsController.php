<?php

namespace App\Http\Controllers\Api\V1\Ai;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Services\AI\EventContextBuilder;
use App\Services\AI\HealthScoreService;
use App\Services\AI\PlannerHistoryService;
use App\Services\AI\ScenarioCalculator;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * AI-driven event analytics: the health score with its breakdown and predictive
 * forecasts, plus the per-domain insight sections the dashboard renders.
 */
class AnalyticsController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly HealthScoreService $health,
        private readonly EventContextBuilder $contextBuilder,
        private readonly ScenarioCalculator $scenarios,
        private readonly PlannerHistoryService $history,
    ) {}

    /** Health score + forecasts for one event. */
    public function analytics(Request $request): JsonResponse
    {
        $user = $request->user();
        $event = $this->plannerEvent($request);

        $score = $this->health->for($user, $event);

        return $this->success([
            'event' => ['id' => $event->id, 'title' => $event->title],
            'health' => [
                'score' => $score->score,
                'label' => $score->label,
                'breakdown' => $score->breakdown,
                'computed_at' => $score->computed_at?->toIso8601String(),
            ],
            'forecasts' => $score->forecasts ?? [],
        ]);
    }

    /** Detailed per-domain insight sections (budget, timeline, guests, ...). */
    public function insights(Request $request): JsonResponse
    {
        $user = $request->user();
        $event = $this->plannerEvent($request);

        $context = $this->contextBuilder->forEvent($user, $event);

        return $this->success([
            'event' => $context['event'] ?? ['id' => $event->id, 'title' => $event->title],
            'budget' => $context['budget'] ?? null,
            'timeline' => $context['timeline'] ?? null,
            'guests' => $context['guests'] ?? null,
            'vendors' => $context['vendors'] ?? null,
            'finance' => $context['finance'] ?? null,
            'benchmark' => $this->history->compareEvent($user, $event),
            'quote_flags' => $this->history->quoteFlags($user, $event),
        ]);
    }

    /**
     * The planner's private benchmarks, mined from their own delivered events:
     * the typical budget category split plus per-service vendor scorecards.
     */
    public function benchmarks(Request $request): JsonResponse
    {
        $user = $request->user();

        return $this->success([
            'has_history' => $this->history->hasHistory($user),
            'budget' => $this->history->budgetBenchmark($user),
            'vendors' => $this->history->vendorScorecards($user),
        ]);
    }

    /**
     * "What-if" calculator: given a guest delta (and optional table size / target
     * budget), return the projected catering cost, tables, meal rollup and a
     * venue-capacity check - all deterministic maths over the event's real data.
     */
    public function scenario(Request $request): JsonResponse
    {
        $request->validate([
            'guests_delta' => ['nullable', 'integer', 'between:-100000,100000'],
            'seats_per_table' => ['nullable', 'integer', 'between:1,100'],
            'target_budget' => ['nullable', 'numeric', 'min:0'],
        ]);

        $user = $request->user();
        $event = $this->plannerEvent($request);

        $context = $this->contextBuilder->forEvent($user, $event);

        $result = $this->scenarios->forEvent($context ?? [], [
            'guests_delta' => $request->integer('guests_delta'),
            'seats_per_table' => $request->integer('seats_per_table') ?: 10,
            'target_budget' => $request->filled('target_budget') ? (float) $request->input('target_budget') : null,
        ]);

        return $this->success(array_merge(
            ['event' => ['id' => $event->id, 'title' => $event->title]],
            $result,
        ));
    }

    private function plannerEvent(Request $request): Event
    {
        $request->validate(['event_id' => ['required', 'integer']]);

        return Event::where('planner_id', $request->user()->id)
            ->findOrFail($request->integer('event_id'));
    }
}
